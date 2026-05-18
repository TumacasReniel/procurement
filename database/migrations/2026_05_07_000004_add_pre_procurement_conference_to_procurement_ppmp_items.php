<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_ppmp_items', 'pre_procurement_conference')) {
                $table->string('pre_procurement_conference')->nullable()->after('recommended_mode_of_procurement');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_ppmp_items', 'pre_procurement_conference')) {
                $table->dropColumn('pre_procurement_conference');
            }
        });
    }
};
