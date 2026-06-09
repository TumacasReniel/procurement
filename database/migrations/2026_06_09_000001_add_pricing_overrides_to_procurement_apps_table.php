<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_apps') || Schema::hasColumn('procurement_apps', 'pricing_overrides')) {
            return;
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            $table->json('pricing_overrides')->nullable()->after('version');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_apps') || ! Schema::hasColumn('procurement_apps', 'pricing_overrides')) {
            return;
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            $table->dropColumn('pricing_overrides');
        });
    }
};
