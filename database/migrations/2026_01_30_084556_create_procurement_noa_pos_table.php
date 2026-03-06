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
        Schema::create('procurement_noa_pos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->unique(); 
            $table->date('po_date'); 
            $table->string('delivery_term'); 
            $table->string('payment_term'); 
            $table->date('date_of_delivery'); 
            $table->integer('procurement_id')->unsigned()->index();
            $table->foreign('procurement_id')->references('id')->on('procurements')->onDelete('cascade');
            $table->tinyInteger('place_of_delivery_id')->unsigned()->index();
            $table->foreign('place_of_delivery_id')->references('id')->on('list_dropdowns');
            $table->integer('noa_id')->unsigned()->index();
            $table->foreign('noa_id')->references('id')->on('procurement_bac_noas');
            $table->integer('created_by_id')->unsigned()->index();
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->integer('approved_by_id')->unsigned()->index()->nullable();
            $table->foreign('approved_by_id')->references('id')->on('users');
            $table->integer('updated_by_id')->unsigned()->index()->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users');
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
        Schema::dropIfExists('procurement_noa_pos');
    }
};
