<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            if (!Schema::hasColumn('procurements', 'classification_id')) {
                $table->unsignedTinyInteger('classification_id')->nullable()->after('fund_cluster_id')->index();
                $table->foreign('classification_id')->references('id')->on('list_dropdowns');
            }

            if (!Schema::hasColumn('procurements', 'reference_app_id')) {
                $table->unsignedTinyInteger('reference_app_id')->nullable()->after('classification_id');
                $table->foreign('reference_app_id')->references('id')->on('list_dropdowns');
            }

            if (!Schema::hasColumn('procurements', 'procurement_app_id')) {
                $table->unsignedInteger('procurement_app_id')->nullable()->after('reference_app_id')->index();
                $table->foreign('procurement_app_id')
                    ->references('id')
                    ->on('procurement_apps')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            if (Schema::hasColumn('procurements', 'classification_id')) {
                $table->dropForeign(['classification_id']);
                $table->dropColumn('classification_id');
            }

            if (Schema::hasColumn('procurements', 'reference_app_id')) {
                $table->dropForeign(['reference_app_id']);
                $table->dropColumn('reference_app_id');
            }

            if (Schema::hasColumn('procurements', 'procurement_app_id')) {
                $table->dropForeign(['procurement_app_id']);
                $table->dropColumn('procurement_app_id');
            }
        });
    }
};
