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
        Schema::create('finance_request_attachments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->unsignedInteger('finance_request_id');
            $table->unsignedInteger('finance_document_id')->nullable();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('uploaded_by_id')->nullable();
            $table->timestamps();

            $table->foreign('finance_request_id')
                ->references('id')
                ->on('finance_requests')
                ->onDelete('cascade');

            $table->foreign('finance_document_id')
                ->references('id')
                ->on('finance_documents')
                ->onDelete('set null');

            $table->foreign('uploaded_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique(['finance_request_id', 'finance_document_id'], 'finance_request_document_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_request_attachments');
    }
};
