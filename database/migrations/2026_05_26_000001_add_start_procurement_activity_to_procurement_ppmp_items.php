<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_ppmp_items', 'start_of_procurement_activity')) {
                $table->date('start_of_procurement_activity')->nullable()->after('pre_procurement_conference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_ppmp_items', 'start_of_procurement_activity')) {
                $table->dropColumn('start_of_procurement_activity');
            }
        });
    }
};
