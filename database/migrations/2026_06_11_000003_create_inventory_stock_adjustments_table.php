<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_stock_adjustments')) {
            return;
        }

        Schema::create('inventory_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->unique();
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('stock_id')->nullable();
            $table->enum('type', ['increase', 'decrease', 'correction']);
            $table->decimal('quantity_before', 12, 2)->default(0);
            $table->decimal('quantity_adjusted', 12, 2);
            $table->decimal('quantity_after', 12, 2)->default(0);
            $table->string('reason');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('adjusted_by_id')->nullable();
            $table->unsignedInteger('approved_by_id')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->date('adjustment_date');
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('stock_id')->references('id')->on('inventory_stocks')->nullOnDelete();
            $table->foreign('adjusted_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('status_id')->references('id')->on('list_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_adjustments');
    }
};
