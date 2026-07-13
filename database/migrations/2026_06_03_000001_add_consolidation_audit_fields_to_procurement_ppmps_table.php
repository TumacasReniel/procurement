<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_ppmps')) {
            return;
        }

        Schema::table('procurement_ppmps', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_ppmps', 'submitted_by_id')) {
                $table->unsignedInteger('submitted_by_id')->nullable()->after('requested_by_id')->index();
            }

            if (! Schema::hasColumn('procurement_ppmps', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by_id');
            }

            if (! Schema::hasColumn('procurement_ppmps', 'reviewed_by_id')) {
                $table->unsignedInteger('reviewed_by_id')->nullable()->after('submitted_at')->index();
            }

            if (! Schema::hasColumn('procurement_ppmps', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
            }

            if (! Schema::hasColumn('procurement_ppmps', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            }

            if (! Schema::hasColumn('procurement_ppmps', 'consolidated_by_id')) {
                $table->unsignedInteger('consolidated_by_id')->nullable()->after('approved_at')->index();
            }

            if (! Schema::hasColumn('procurement_ppmps', 'consolidated_at')) {
                $table->timestamp('consolidated_at')->nullable()->after('consolidated_by_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_ppmps')) {
            return;
        }

        Schema::table('procurement_ppmps', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_ppmps', 'consolidated_at')) {
                $table->dropColumn('consolidated_at');
            }

            if (Schema::hasColumn('procurement_ppmps', 'consolidated_by_id')) {
                $table->dropIndex(['consolidated_by_id']);
                $table->dropColumn('consolidated_by_id');
            }

            if (Schema::hasColumn('procurement_ppmps', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('procurement_ppmps', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }

            if (Schema::hasColumn('procurement_ppmps', 'reviewed_by_id')) {
                $table->dropIndex(['reviewed_by_id']);
                $table->dropColumn('reviewed_by_id');
            }

            if (Schema::hasColumn('procurement_ppmps', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            if (Schema::hasColumn('procurement_ppmps', 'submitted_by_id')) {
                $table->dropIndex(['submitted_by_id']);
                $table->dropColumn('submitted_by_id');
            }
        });
    }
};
