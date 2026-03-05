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
        Schema::create('procurement_po_iars', function (Blueprint $table) {
            $table->id();
            $table->Integer('procurement_id')->unsigned()->index();
            $table->foreign('procurement_id')->references('id')->on('procurements');
            $table->integer('po_id')->unsigned()->index();
            $table->foreign('po_id')->references('id')->on('procurement_noa_pos');
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_po_iars');
    }
};
