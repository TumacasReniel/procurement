<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_ppmp_items')) {
            return;
        }

        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_ppmp_items', 'requested_quantity')) {
                $table->decimal('requested_quantity', 15, 4)->nullable()->after('remarks');
            }

            if (! Schema::hasColumn('procurement_ppmp_items', 'funded_quantity')) {
                $table->decimal('funded_quantity', 15, 4)->nullable()->after('requested_quantity');
            }

            if (! Schema::hasColumn('procurement_ppmp_items', 'is_partial_funding')) {
                $table->boolean('is_partial_funding')->default(false)->after('funded_quantity');
            }

            if (! Schema::hasColumn('procurement_ppmp_items', 'price_basis')) {
                $table->string('price_basis')->nullable()->after('item_unit_cost');
            }

            if (! Schema::hasColumn('procurement_ppmp_items', 'price_basis_amount')) {
                $table->decimal('price_basis_amount', 15, 2)->nullable()->after('price_basis');
            }

            if (! Schema::hasColumn('procurement_ppmp_items', 'quantity_adjustment_reason')) {
                $table->text('quantity_adjustment_reason')->nullable()->after('price_basis_amount');
            }

            if (! Schema::hasColumn('procurement_ppmp_items', 'price_variance_reason')) {
                $table->text('price_variance_reason')->nullable()->after('quantity_adjustment_reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_ppmp_items')) {
            return;
        }

        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            foreach ([
                'price_variance_reason',
                'quantity_adjustment_reason',
                'price_basis_amount',
                'price_basis',
                'is_partial_funding',
                'funded_quantity',
                'requested_quantity',
            ] as $column) {
                if (Schema::hasColumn('procurement_ppmp_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
