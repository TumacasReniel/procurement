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
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('code')->unique();
            $table->unsignedInteger('inspected_by_id')->nullable();
            $table->foreign('inspected_by_id')->references('id')->on('users');
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
