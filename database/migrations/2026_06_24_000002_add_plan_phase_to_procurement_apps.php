<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_apps')) {
            return;
        }

        if (! Schema::hasColumn('procurement_apps', 'plan_phase')) {
            Schema::table('procurement_apps', function (Blueprint $table) {
                // 'final' default so all existing APPs are treated as Final APPs (backward compat).
                // New APPs will have plan_phase set explicitly based on user selection.
                $table->string('plan_phase')->default('final')->after('version');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('procurement_apps') && Schema::hasColumn('procurement_apps', 'plan_phase')) {
            Schema::table('procurement_apps', function (Blueprint $table) {
                $table->dropColumn('plan_phase');
            });
        }
    }
};
