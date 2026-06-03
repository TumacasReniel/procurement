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

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->foreignId('unit_id')->constrained('unit_types')->onDelete('cascade');
            $table->decimal('unit_cost', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
