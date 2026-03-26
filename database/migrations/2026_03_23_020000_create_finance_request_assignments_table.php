<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_request_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('finance_request_id');
            $table->string('status', 100);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('finance_request_id')
                ->references('id')
                ->on('finance_requests')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('created_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique(['finance_request_id', 'status', 'user_id'], 'finance_request_status_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_request_assignments');
    }
};
