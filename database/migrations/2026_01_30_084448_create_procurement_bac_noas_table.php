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
        Schema::create('procurement_bac_noas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->unique(); 
            $table->string('remarks')->nullable(); 
            $table->integer('procurement_id')->unsigned()->index();
            $table->foreign('procurement_id')->references('id')->on('procurements')->onDelete('cascade');
            $table->integer('procurement_bac_id')->unsigned()->index();
            $table->foreign('procurement_bac_id')->references('id')->on('procurement_bacs');
            $table->integer('procurement_quotation_id')->unsigned()->index();
            $table->foreign('procurement_quotation_id')->references('id')->on('procurement_quotations');
            $table->integer('created_by_id')->unsigned()->index();
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->integer('approved_by_id')->unsigned()->index()->nullable();
            $table->foreign('approved_by_id')->references('id')->on('users');
            $table->tinyInteger('status_id')->unsigned()->index();
            $table->foreign('status_id')->references('id')->on('list_statuses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_bac_noas');
    }
};
