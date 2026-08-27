<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_drains', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('inventory_stock_id');
            $table->unsignedInteger('item_id');
            $table->string('source_type', 30);
            $table->unsignedInteger('source_id');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);

            $table->foreign('inventory_stock_id')->references('id')->on('inventory_stocks')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_drains');
    }
};
