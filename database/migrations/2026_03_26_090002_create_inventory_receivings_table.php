<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_receivings')) {
            return;
        }

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unsignedInteger('item_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->unsignedInteger('po_id')->nullable();
            $table->unsignedInteger('procurement_item_id')->nullable();
            $table->unsignedInteger('approved_by_id')->nullable();
            $table->unsignedTinyInteger('status_id');

            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('status_id')->references('id')->on('list_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receivings');
    }
};
