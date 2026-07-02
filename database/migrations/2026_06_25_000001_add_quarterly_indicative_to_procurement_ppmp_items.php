<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_ppmp_items')) {
            return;
        }

        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            foreach ([
                'q1_indicative_amount',
                'q2_indicative_amount',
                'q3_indicative_amount',
                'q4_indicative_amount',
            ] as $column) {
                if (! Schema::hasColumn('procurement_ppmp_items', $column)) {
                    $table->decimal($column, 15, 2)->nullable()->after('total_cost');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_ppmp_items')) {
            return;
        }

        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            foreach ([
                'q4_indicative_amount',
                'q3_indicative_amount',
                'q2_indicative_amount',
                'q1_indicative_amount',
            ] as $column) {
                if (Schema::hasColumn('procurement_ppmp_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
