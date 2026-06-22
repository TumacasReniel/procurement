<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // inventory_items: legacy columns that are NOT NULL with no default break inserts
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'expiration')) {
                $table->date('expiration')->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('inventory_items', 'stock_id')) {
                $table->unsignedInteger('stock_id')->nullable()->default(null)->change();
            }
        });

        // inventory_stocks: legacy code/name columns that are NOT NULL with no default
        Schema::table('inventory_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_stocks', 'code')) {
                $table->string('code')->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('inventory_stocks', 'name')) {
                $table->string('name')->nullable()->default(null)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'expiration')) {
                $table->date('expiration')->nullable(false)->change();
            }
            if (Schema::hasColumn('inventory_items', 'stock_id')) {
                $table->unsignedInteger('stock_id')->nullable(false)->change();
            }
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_stocks', 'code')) {
                $table->string('code')->nullable(false)->change();
            }
            if (Schema::hasColumn('inventory_stocks', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
        });
    }
};
