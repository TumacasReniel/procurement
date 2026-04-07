<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->integer('quantity');
            $table->integer('unit_cost');
            $table->date('expiration');
            $table->timestamps();

            $table->unsignedInteger('stock_id')->index();
            $table->foreign('stock_id')
                ->references('id')
                ->on('inventory_stocks');

            $table->unsignedTinyInteger('category_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('list_dropdowns');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
