<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix: first run left a BIGINT UNSIGNED column (type mismatch with INT UNSIGNED id).
        // Drop and re-add with the correct type before adding the FK.
        if (Schema::hasColumn('procurement_ppmps', 'source_ppmp_id')) {
            Schema::table('procurement_ppmps', function (Blueprint $table) {
                $table->dropColumn('source_ppmp_id');
            });
        }

        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->unsignedInteger('source_ppmp_id')->nullable()->after('ppmp_type_version');
            $table->foreign('source_ppmp_id')->references('id')->on('procurement_ppmps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropForeign(['source_ppmp_id']);
            $table->dropColumn('source_ppmp_id');
        });
    }
};
