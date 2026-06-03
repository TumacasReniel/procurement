<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('approved_by_id')->nullable()->constrained('users');
            $table->foreignId('status_id')->constrained('list_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receivings');
    }
};
