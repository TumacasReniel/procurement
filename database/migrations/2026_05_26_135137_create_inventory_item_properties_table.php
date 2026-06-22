<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_item_properties')) {
            return;
        }

        Schema::create('inventory_item_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inventory_item_id');
            $table->string('property_code');
            $table->string('model');
            $table->string('serial_no');
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 10, 2);
            $table->decimal('depreciation_rate', 5, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_properties');
    }
};
