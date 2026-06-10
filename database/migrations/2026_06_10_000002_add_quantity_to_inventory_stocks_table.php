<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return;
        }

        Schema::table('inventory_stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_stocks', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('inventory_stocks', 'unit_id')) {
                $table->unsignedTinyInteger('unit_id')->nullable();
            }
            if (! Schema::hasColumn('inventory_stocks', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Not reversing — these are essential columns
    }
};
