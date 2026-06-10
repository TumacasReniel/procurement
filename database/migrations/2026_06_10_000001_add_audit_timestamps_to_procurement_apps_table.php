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

        Schema::table('procurement_apps', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_apps', 'submitted_by_id')) {
                $table->unsignedInteger('submitted_by_id')->nullable()->after('approved_by_id');
            }
            if (! Schema::hasColumn('procurement_apps', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by_id');
            }
            if (! Schema::hasColumn('procurement_apps', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
            }
            if (! Schema::hasColumn('procurement_apps', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_apps')) {
            return;
        }

        Schema::table('procurement_apps', function (Blueprint $table) {
            foreach (['submitted_by_id', 'submitted_at', 'reviewed_at', 'approved_at'] as $column) {
                if (Schema::hasColumn('procurement_apps', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
