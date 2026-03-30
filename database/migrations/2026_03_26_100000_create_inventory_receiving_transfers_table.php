<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_receiving_transfers')) {
            Schema::drop('inventory_receiving_transfers');
        }

        Schema::create('inventory_receiving_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('po_id');
            $table->foreign('po_id')->references('id')->on('procurement_noa_pos')->cascadeOnDelete();
            $table->unsignedInteger('procurement_item_id');
            $table->foreign('procurement_item_id')->references('id')->on('procurement_items')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('inventory_stock_id')->constrained('inventory_stocks')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();

            $table->unique(['po_id', 'procurement_item_id'], 'inventory_receiving_transfers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receiving_transfers');
    }
};
