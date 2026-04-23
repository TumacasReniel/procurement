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
        Schema::create('supplier_attachments', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('path');
            $table->date('expired_at')->nullable();
            $table->boolean('is_renewable')->default(0);
            $table->boolean('is_active')->default(1);
            $table->unsignedSmallInteger('type_id')->nullable();
            $table->foreign('type_id')->references('id')->on('list_data')->onDelete('cascade');
            $table->unsignedInteger('supplier_id');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->string('document_type')->nullable();
            $table->string('code')->nullable()->change();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_attachments');
    }
};
