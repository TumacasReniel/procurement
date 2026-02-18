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
        Schema::create('procurement_bac_noa_items', function (Blueprint $table) {
            $table->id();
            $table->integer('procurement_bac_noa_id')->unsigned()->index();
            $table->foreign('procurement_bac_noa_id')->references('id')->on('procurement_bac_noas');
            $table->integer('item_id')->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('procurement_quotation_items');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_bac_noa_items');
    }
};
