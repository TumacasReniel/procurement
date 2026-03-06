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
        Schema::create('procurement_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('item_no');
            $table->Integer('procurement_id')->unsigned()->index();
            $table->foreign('procurement_id')->references('id')->on('procurements')->onDelete('cascade');;
            $table->tinyInteger('item_unit_type_id')->unsigned()->index();;
            $table->foreign('item_unit_type_id')->references('id')->on('list_dropdowns')->onDelete('cascade');;
            $table->text('item_description');
            $table->string('item_quantity');
            $table->decimal('item_unit_cost');
            $table->decimal('total_cost');
            $table->tinyInteger('status_id')->unsigned()->index()->nullable();
            $table->foreign('status_id')->references('id')->on('list_statuses')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
    }
};
