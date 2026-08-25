<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_ppmp_projects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('procurement_ppmp_id')->index();
            $table->string('title')->nullable();
            $table->string('project_type')->nullable();
            $table->string('recommended_mode_of_procurement')->nullable();
            $table->string('pre_procurement_conference')->nullable();
            $table->date('start_of_procurement_activity')->nullable();
            $table->date('end_of_procurement_activity')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('attached_supporting_documents')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('project_total_budget', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('procurement_ppmp_id')
                ->references('id')
                ->on('procurement_ppmps')
                ->onDelete('cascade');
        });

        if (Schema::hasColumn('procurement_ppmps', 'project_type')) {
            $this->backfillProjectRows();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_ppmp_projects');
    }

    /**
     * Collapse each unit/year/type/quarter "sibling group" of duplicate procurement_ppmps
     * rows (created historically by createSiblingPpmp() to hold one project each) down to
     * one surviving row per group, moving each row's project fields into its own
     * procurement_ppmp_projects child row and soft-deleting the redundant siblings.
     */
    private function backfillProjectRows(): void
    {
        $hasQuarter = Schema::hasColumn('procurement_ppmps', 'quarter');
        $hasPpmpType = Schema::hasColumn('procurement_ppmps', 'ppmp_type');

        $rows = DB::table('procurement_ppmps')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $groups = $rows
            // SPP rows are a different plan concept entirely, never siblings of a PPMP quarter.
            ->filter(fn ($r) => (empty($r->title) || $r->title !== 'Supplemental Procurement Plan')
                && (empty($r->code) || ! str_starts_with((string) $r->code, 'SPP-')))
            ->groupBy(fn ($r) => implode('|', [
                $r->unit_id,
                $r->date ? date('Y', strtotime($r->date)) : 'null',
                $hasPpmpType ? ($r->ppmp_type ?: 'indicative') : 'indicative',
                $hasQuarter ? ($r->quarter ?? 0) : 0,
            ]));

        foreach ($groups as $group) {
            $survivor = $group->sortBy('id')->first();

            foreach ($group as $row) {
                if (! empty($row->project_type)) {
                    DB::table('procurement_ppmp_projects')->insert([
                        'procurement_ppmp_id' => $survivor->id,
                        'title' => $row->title,
                        'project_type' => $row->project_type,
                        'recommended_mode_of_procurement' => $row->recommended_mode_of_procurement ?? null,
                        'pre_procurement_conference' => $row->pre_procurement_conference ?? null,
                        'start_of_procurement_activity' => $row->start_of_procurement_activity ?? null,
                        'end_of_procurement_activity' => $row->end_of_procurement_activity ?? null,
                        'expected_delivery_date' => $row->expected_delivery_date ?? null,
                        'attached_supporting_documents' => $row->attached_supporting_documents ?? null,
                        'remarks' => $row->remarks ?? null,
                        'project_total_budget' => $row->project_total_budget ?? null,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }

                if ($row->id !== $survivor->id) {
                    DB::table('procurement_ppmp_items')
                        ->where('procurement_ppmp_id', $row->id)
                        ->update(['procurement_ppmp_id' => $survivor->id]);

                    DB::table('procurement_ppmps')
                        ->where('id', $row->id)
                        ->update(['deleted_at' => now()]);
                }
            }
        }
    }
};
