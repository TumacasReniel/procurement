<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete();
            $table->foreignId('inventory_stock_id')->nullable()->constrained('inventory_stocks')->nullOnDelete();
            $table->unsignedTinyInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('list_dropdowns')->nullOnDelete();
            $table->unsignedInteger('requested_by_id')->nullable();
            $table->foreign('requested_by_id')->references('id')->on('users')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamp('released_at')->nullable();
            $table->string('status')->default('Released');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_withdrawals');
    }
};
