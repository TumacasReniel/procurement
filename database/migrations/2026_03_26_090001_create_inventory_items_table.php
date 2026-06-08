<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items')) return;

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->timestamps();
            $table->tinyInteger('category_id')->unsigned()->index()->nullable();
            $table->foreign('category_id')->references('id')->on('list_dropdowns')->onDelete('set null');

        });

        if (!Schema::hasTable('inventory_stocks')) {
            Schema::create('inventory_stocks', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('item_id');
                $table->decimal('quantity', 12, 2)->default(0);
                $table->unsignedTinyInteger('unit_id');
                $table->decimal('unit_cost', 10, 2);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('cascade');
                $table->foreign('unit_id')->references('id')->on('unit_types')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('inventory_items');
    }
};
