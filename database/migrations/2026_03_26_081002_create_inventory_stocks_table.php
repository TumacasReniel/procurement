<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_stocks')) {
            Schema::table('inventory_stocks', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_stocks', 'description')) {
                    $table->text('description')->nullable()->after('unit_cost');
                }
            });
            return;
        }

        if (!Schema::hasTable('inventory_items')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
