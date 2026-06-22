<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->string('name')->unique();
                $table->decimal('reorder_level', 12, 2)->default(0);
                $table->unsignedTinyInteger('category_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('list_dropdowns')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('inventory_stocks')) {
            Schema::create('inventory_stocks', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('item_id');
                $table->decimal('quantity', 12, 2)->default(0);
                $table->unsignedInteger('unit_id');
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->text('description')->nullable();
                $table->date('expiration_date')->nullable();
                $table->timestamps();

                $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
                $table->foreign('unit_id')->references('id')->on('unit_types')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('inventory_items');
    }
};
