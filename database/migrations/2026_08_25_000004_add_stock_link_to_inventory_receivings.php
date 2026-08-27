<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_receivings', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_receivings', 'inventory_stock_id')) {
                $table->unsignedInteger('inventory_stock_id')->nullable()->after('item_id');
                $table->foreign('inventory_stock_id')->references('id')->on('inventory_stocks')->nullOnDelete();
            }
            if (! Schema::hasColumn('inventory_receivings', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_receivings', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_receivings', 'inventory_stock_id')) {
                $table->dropForeign(['inventory_stock_id']);
                $table->dropColumn('inventory_stock_id');
            }
            if (Schema::hasColumn('inventory_receivings', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });
    }
};
