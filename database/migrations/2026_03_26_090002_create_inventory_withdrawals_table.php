<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_withdrawals', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('released_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->foreignId('inventory_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('requested_by_id')->constrained('users');
            $table->foreignId('approved_by_id')->nullable()->constrained('users');
            $table->foreignId('status_id')->constrained('list_statuses');   
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_withdrawals');
    }
};
