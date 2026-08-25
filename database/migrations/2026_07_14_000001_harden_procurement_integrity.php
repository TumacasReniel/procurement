<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that hold procurement documents. Deleting any of these destroys part of
     * the audit trail, so they get soft deletes instead of hard deletes.
     *
     * This list must stay in sync with the models that use the SoftDeletes trait —
     * a model with the trait but no deleted_at column breaks every query against it.
     */
    protected array $softDeleteTables = [
        'procurements',
        'procurement_items',
        'procurement_quotations',
        'procurement_quotation_items',
        'procurement_bacs',
        'procurement_bac_noas',
        'procurement_noa_pos',
        'procurement_ppmps',
        'procurement_ppmp_items',
        'procurement_apps',
    ];

    public function up(): void
    {
        // Soft deletes first: the unique index below has to account for trashed rows,
        // which keep their code so it can never be silently reissued.
        foreach ($this->softDeleteTables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }

        // The original migration wrote ->unitque(), which Laravel's Fluent silently
        // ignores, so procurements.code has never actually been unique. Deduplicate
        // any existing collisions before the index can be created.
        if (! $this->hasIndex('procurements', 'procurements_code_unique')) {
            $this->deduplicateProcurementCodes();

            Schema::table('procurements', function (Blueprint $table) {
                $table->unique('code', 'procurements_code_unique');
            });
        }

        // Composite indexes for the hot dashboard/report paths. deleted_at trails the
        // filter columns so the SoftDeletes global scope is resolved from the index
        // instead of a row lookup.
        $this->addIndexIfMissing('procurements', ['date', 'status_id', 'deleted_at'], 'procurements_date_status_index');
        $this->addIndexIfMissing('procurement_ppmps', ['unit_id', 'status_id', 'deleted_at'], 'procurement_ppmps_unit_status_index');
        $this->addIndexIfMissing('procurement_code_budget_logs', ['procurement_id', 'type'], 'pcbl_procurement_type_index');

        // Child rows are reached by parent id on every detail screen and every budget
        // aggregate, and each of those queries now also filters deleted_at.
        $this->addIndexIfMissing('procurement_items', ['procurement_id', 'deleted_at'], 'procurement_items_procurement_deleted_index');
        $this->addIndexIfMissing('procurement_quotation_items', ['quotation_id', 'deleted_at'], 'pqi_quotation_deleted_index');
        $this->addIndexIfMissing('procurement_ppmp_items', ['procurement_ppmp_id', 'deleted_at'], 'ppi_ppmp_deleted_index');
    }

    public function down(): void
    {
        // Drop the indexes before the columns they span, otherwise dropping
        // deleted_at leaves a dangling index definition on MySQL.
        $this->dropIndexIfExists('procurements', 'procurements_code_unique', true);
        $this->dropIndexIfExists('procurements', 'procurements_date_status_index');
        $this->dropIndexIfExists('procurement_ppmps', 'procurement_ppmps_unit_status_index');
        $this->dropIndexIfExists('procurement_code_budget_logs', 'pcbl_procurement_type_index');
        $this->dropIndexIfExists('procurement_items', 'procurement_items_procurement_deleted_index');
        $this->dropIndexIfExists('procurement_quotation_items', 'pqi_quotation_deleted_index');
        $this->dropIndexIfExists('procurement_ppmp_items', 'ppi_ppmp_deleted_index');

        foreach ($this->softDeleteTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }

    protected function deduplicateProcurementCodes(): void
    {
        $duplicates = DB::table('procurements')
            ->select('code')
            ->whereNotNull('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code');

        foreach ($duplicates as $code) {
            // Keep the earliest row on the original code; suffix the rest so the
            // unique index can be created without losing any record.
            $ids = DB::table('procurements')
                ->where('code', $code)
                ->orderBy('id')
                ->pluck('id')
                ->slice(1);

            foreach ($ids as $id) {
                $suffix = '-DUP'.$id;

                DB::table('procurements')
                    ->where('id', $id)
                    // Trim the original rather than overflow varchar(255), which
                    // would either truncate under a lax sql_mode or abort the
                    // migration under a strict one.
                    ->update(['code' => mb_substr($code, 0, 255 - mb_strlen($suffix)).$suffix]);
            }
        }
    }

    protected function hasIndex(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return collect(Schema::getIndexes($table))
            ->contains(fn (array $row) => $row['name'] === $index);
    }

    protected function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    protected function dropIndexIfExists(string $table, string $index, bool $unique = false): void
    {
        if (! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index, $unique) {
            $unique ? $blueprint->dropUnique($index) : $blueprint->dropIndex($index);
        });
    }
};
