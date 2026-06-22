<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('procurement_apps')) {
            Schema::create('procurement_apps', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('code')->unique();
                $table->unsignedSmallInteger('year')->unique();
                $table->string('title')->default('Annual Procurement Plan');
                $table->unsignedSmallInteger('app_type_id')->nullable()->index();
                $table->unsignedInteger('created_by_id')->nullable()->index();
                $table->unsignedInteger('requested_by_id')->nullable();
                $table->unsignedInteger('reviewed_by_id')->nullable();
                $table->unsignedInteger('approved_by_id')->nullable();
                $table->unsignedTinyInteger('status_id')->nullable();
                $table->unsignedTinyInteger('sub_status_id')->nullable();
                $table->unsignedInteger('submitted_by_id')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('procurement_ppmps')) {
            Schema::table('procurement_ppmps', function (Blueprint $table) {
                if (!Schema::hasColumn('procurement_ppmps', 'procurement_app_id')) {
                    $table->unsignedInteger('procurement_app_id')->nullable()->after('reference_app_id')->index();
                    $table->foreign('procurement_app_id')
                        ->references('id')
                        ->on('procurement_apps')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('procurements')) {
            Schema::table('procurements', function (Blueprint $table) {
                if (!Schema::hasColumn('procurements', 'procurement_app_id')) {
                    $table->unsignedInteger('procurement_app_id')->nullable()->after('reference_app_id')->index();
                    $table->foreign('procurement_app_id')
                        ->references('id')
                        ->on('procurement_apps')
                        ->nullOnDelete();
                }
            });
        }

    }

    public function down(): void
    {
        if (Schema::hasTable('procurements')) {
            Schema::table('procurements', function (Blueprint $table) {
                if (Schema::hasColumn('procurements', 'procurement_app_id')) {
                    $table->dropForeign(['procurement_app_id']);
                    $table->dropColumn('procurement_app_id');
                }
            });
        }

        if (Schema::hasTable('procurement_ppmps')) {
            Schema::table('procurement_ppmps', function (Blueprint $table) {
                if (Schema::hasColumn('procurement_ppmps', 'procurement_app_id')) {
                    $table->dropForeign(['procurement_app_id']);
                    $table->dropColumn('procurement_app_id');
                }
            });
        }

        Schema::dropIfExists('procurement_apps');
    }


};
