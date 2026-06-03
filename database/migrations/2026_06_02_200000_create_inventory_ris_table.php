<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_ris', function (Blueprint $table) {
            $table->id();
            $table->string('ris_no')->unique();
            $table->string('fund_cluster')->nullable();
            $table->string('division')->nullable();
            $table->string('responsibility_center')->nullable();
            $table->text('purpose')->nullable();
            $table->date('ris_date');
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('status_id')->constrained('list_statuses');
            $table->timestamps();
        });

        Schema::create('inventory_ris_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ris_id')->constrained('inventory_ris')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('unit_of_issue')->nullable();
            $table->decimal('quantity_requested', 12, 2)->default(0);
            $table->decimal('quantity_issued', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_ris_items');
        Schema::dropIfExists('inventory_ris');
    }
};
