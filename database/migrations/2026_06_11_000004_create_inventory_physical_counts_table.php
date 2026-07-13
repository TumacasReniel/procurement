<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_physical_counts')) {
            return;
        }

        Schema::create('inventory_physical_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_no')->unique();
            $table->date('count_date');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('counted_by_id')->nullable();
            $table->unsignedInteger('verified_by_id')->nullable();
            $table->unsignedInteger('approved_by_id')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->timestamps();

            $table->foreign('counted_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('status_id')->references('id')->on('list_statuses');
        });

        Schema::create('inventory_physical_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_id')->constrained('inventory_physical_counts')->cascadeOnDelete();
            $table->unsignedInteger('item_id');
            $table->decimal('system_quantity', 12, 2)->default(0);
            $table->decimal('physical_quantity', 12, 2)->default(0);
            $table->decimal('variance', 12, 2)->storedAs('physical_quantity - system_quantity');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_physical_count_items');
        Schema::dropIfExists('inventory_physical_counts');
    }
};
