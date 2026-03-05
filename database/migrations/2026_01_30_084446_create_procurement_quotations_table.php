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
        Schema::create('procurement_quotations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->unique(); 
            $table->date('submission_not_later_than'); 
            $table->string('delivery_term')->nullable(); 
            $table->tinyInteger('place_of_delivery_id')->unsigned()->index()->nullable();
            $table->foreign('place_of_delivery_id')->references('id')->on('list_dropdowns')->onDelete('cascade');;
            $table->Integer('supplier_id')->unsigned()->index();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');;
            $table->Integer('supply_officer_id')->unsigned()->index();
            $table->foreign('supply_officer_id')->references('id')->on('users');
            $table->Integer('procurement_id')->unsigned()->index();
            $table->foreign('procurement_id')->references('id')->on('procurements')->onDelete('cascade');;
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
        Schema::dropIfExists('procurement_quotations');
    }
};
