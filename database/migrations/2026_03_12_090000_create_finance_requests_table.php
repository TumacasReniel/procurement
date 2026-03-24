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
        Schema::create('finance_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedBigInteger('request_id');
            $table->string('code', 50)->nullable()->unique();
            $table->date('date')->nullable();
            $table->unsignedInteger('request_type_id')->nullable();
            $table->unsignedTinyInteger('division_id')->nullable();
            $table->unsignedInteger('fund_cluster_id')->nullable();
            $table->unsignedBigInteger('creditor_id')->nullable();
            $table->string('creditor_type', 255)->nullable();
            $table->string('payee')->nullable();
            $table->unsignedInteger('obligation_type_id')->nullable();
            $table->unsignedInteger('project_type_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedTinyInteger('status_id')->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
           
            // Request Details
            
            $table->string('requested_by')->nullable();
            $table->decimal('amount', 15, 2)->nullable()->default(0);
            $table->text('particulars')->nullable();
            
    
           
            
            $table->timestamps();

            // Foreign Keys
            $table->foreign('request_id')
                ->references('id')
                ->on('requests');

            $table->foreign('request_type_id')
                ->references('id')
                ->on('finance_request_types');
                
            $table->foreign('division_id')
                ->references('id')
                ->on('list_units');

            $table->index(['creditor_id', 'creditor_type']);
                
            $table->foreign('status_id')
                ->references('id')
                ->on('list_statuses')
                ->onDelete('set null');
                
            $table->foreign('created_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_requests');
    }
};
