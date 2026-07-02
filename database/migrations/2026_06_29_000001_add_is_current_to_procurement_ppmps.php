<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('procurement_ppmps', 'is_current')) {
            return;
        }

        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->boolean('is_current')->default(true)->after('source_ppmp_id');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });
    }
};
