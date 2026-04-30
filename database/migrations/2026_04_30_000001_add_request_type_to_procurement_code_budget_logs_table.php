<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_code_budget_logs', function (Blueprint $table) {
            $table->string('request_type', 30)->default('additional_budget')->after('status');
            $table->unsignedInteger('source_procurement_code_id')->nullable()->after('procurement_code_id')->index();

            $table->foreign('source_procurement_code_id', 'procurement_code_budget_logs_source_code_fk')
                ->references('id')
                ->on('procurement_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_code_budget_logs', function (Blueprint $table) {
            $table->dropForeign('procurement_code_budget_logs_source_code_fk');
            $table->dropColumn(['request_type', 'source_procurement_code_id']);
        });
    }
};
