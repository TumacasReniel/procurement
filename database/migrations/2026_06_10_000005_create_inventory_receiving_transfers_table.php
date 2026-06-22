<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_receiving_transfers')) {
            return;
        }

        Schema::create('inventory_receiving_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('po_id');
            $table->unsignedInteger('procurement_item_id')->nullable();
            $table->unsignedInteger('inventory_id');
            $table->unsignedInteger('inventory_stock_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();

            $table->foreign('po_id')->references('id')->on('procurement_noa_pos')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('inventory_stock_id')->references('id')->on('inventory_stocks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receiving_transfers');
    }
};
