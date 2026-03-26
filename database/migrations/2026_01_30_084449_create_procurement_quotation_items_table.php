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
        Schema::create('procurement_quotation_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('quotation_id')->unsigned()->index();
            $table->foreign('quotation_id')->references('id')->on('procurement_quotations')->onDelete('cascade');;
            $table->integer('procurement_item_id')->unsigned()->index();;
            $table->foreign('procurement_item_id')->references('id')->on('procurement_items')->onDelete('cascade');;
            $table->text('technical_proposal')->nullable(); 
            $table->string('bid_price')->nullable(); 
            $table->boolean('is_checked')->default(0); 
            $table->boolean('is_rebid')->default(0); 
            $table->tinyInteger('status_id')->unsigned()->index();
            $table->foreign('status_id')->references('id')->on('list_statuses')->onDelete('cascade');;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_quotation_items');
    }
};
