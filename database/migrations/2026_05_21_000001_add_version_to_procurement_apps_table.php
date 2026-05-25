<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_apps')) {
            return;
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_apps', 'version')) {
                $table->unsignedSmallInteger('version')->default(1)->after('year');
            }
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $indexes = collect(DB::select('SHOW INDEX FROM procurement_apps'))
                ->pluck('Key_name')
                ->unique();

            if ($indexes->contains('procurement_apps_year_unique')) {
                Schema::table('procurement_apps', function (Blueprint $table) {
                    $table->dropUnique('procurement_apps_year_unique');
                });
            }
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            $table->unique(['year', 'version'], 'procurement_apps_year_version_unique');
        });

    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_apps')) {
            return;
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            $table->dropUnique('procurement_apps_year_version_unique');
        });

        if (Schema::hasColumn('procurement_apps', 'version')) {
            Schema::table('procurement_apps', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            $table->unique('year', 'procurement_apps_year_unique');
        });
    }
};
