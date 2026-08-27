<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'inventory_items',
        'inventory_stocks',
        'inventory_receivings',
        'inventory_withdrawals',
        'inventory_ris',
        'inventory_ris_items',
        'inventory_ics',
        'inventory_ics_items',
        'inventory_par',
        'inventory_par_items',
        'inventory_physical_counts',
        'inventory_physical_count_items',
        'inventory_stock_adjustments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
