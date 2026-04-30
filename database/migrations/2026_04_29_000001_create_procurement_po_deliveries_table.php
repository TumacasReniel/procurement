<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_po_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('procurement_id')->index();
            $table->unsignedInteger('po_id')->index();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->json('delivered_items')->nullable();
            $table->unsignedInteger('received_by_id')->nullable();
            $table->timestamps();

            $table->foreign('procurement_id')->references('id')->on('procurements');
            $table->foreign('po_id')->references('id')->on('procurement_noa_pos');
            $table->foreign('received_by_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_po_deliveries');
    }
};
