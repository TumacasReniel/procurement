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
        Schema::create('procurement_code_budget_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('procurement_code_id')->index();
            $table->unsignedInteger('procurement_id')->nullable()->index();
            $table->unsignedInteger('processed_by_id')->nullable()->index();
            $table->unsignedInteger('requested_by_id')->nullable()->index();
            $table->unsignedInteger('reviewed_by_id')->nullable()->index();
            $table->string('type', 50)->default('approval_deduction');
            $table->string('status', 20)->default('approved')->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('description')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('procurement_code_id')
                ->references('id')
                ->on('procurement_codes')
                ->onDelete('cascade');
            $table->foreign('procurement_id')
                ->references('id')
                ->on('procurements')
                ->onDelete('cascade');
            $table->foreign('processed_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('requested_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('reviewed_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(
                ['procurement_code_id', 'procurement_id', 'type'],
                'procurement_code_budget_logs_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_code_budget_logs');
    }
};
