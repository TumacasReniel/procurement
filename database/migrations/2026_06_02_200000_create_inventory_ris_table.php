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
            $table->unsignedInteger('requested_by_id')->nullable();
            $table->unsignedInteger('approved_by_id')->nullable();
            $table->unsignedInteger('issued_by_id')->nullable();
            $table->unsignedInteger('received_by_id')->nullable();
            $table->unsignedTinyInteger('status_id');
            $table->timestamps();

            $table->foreign('requested_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('issued_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('received_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('status_id')->references('id')->on('list_statuses');
        });

        Schema::create('inventory_ris_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ris_id')->constrained('inventory_ris')->cascadeOnDelete();
            $table->unsignedInteger('item_id');
            $table->string('unit_of_issue')->nullable();
            $table->decimal('quantity_requested', 12, 2)->default(0);
            $table->decimal('quantity_issued', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_ris_items');
        Schema::dropIfExists('inventory_ris');
    }
};
