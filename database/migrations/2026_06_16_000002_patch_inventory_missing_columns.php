<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'reorder_level')) {
                $table->decimal('reorder_level', 12, 2)->default(0)->after('name');
            }
        });

        Schema::table('inventory_withdrawals', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_withdrawals', 'issued_quantity')) {
                $table->decimal('issued_quantity', 12, 2)->default(0)->after('quantity');
            }
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_stocks', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('description');
            }
        });

        if (Schema::hasColumn('inventory_item_properties', 'aquisition_date') &&
            !Schema::hasColumn('inventory_item_properties', 'acquisition_date')) {
            Schema::table('inventory_item_properties', function (Blueprint $table) {
                $table->renameColumn('aquisition_date', 'acquisition_date');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_stocks', 'expiration_date')) {
                $table->dropColumn('expiration_date');
            }
        });

        if (Schema::hasColumn('inventory_item_properties', 'acquisition_date') &&
            !Schema::hasColumn('inventory_item_properties', 'aquisition_date')) {
            Schema::table('inventory_item_properties', function (Blueprint $table) {
                $table->renameColumn('acquisition_date', 'aquisition_date');
            });
        }
    }
};
