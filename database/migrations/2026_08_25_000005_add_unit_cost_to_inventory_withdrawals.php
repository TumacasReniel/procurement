<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_withdrawals', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_withdrawals', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 4)->nullable()->after('issued_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_withdrawals', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });
    }
};
