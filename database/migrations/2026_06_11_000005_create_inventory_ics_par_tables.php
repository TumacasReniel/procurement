<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ICS — Inventory Custodian Slip (semi-expendable items)
        if (!Schema::hasTable('inventory_ics')) {
            Schema::create('inventory_ics', function (Blueprint $table) {
                $table->id();
                $table->string('ics_no')->unique();
                $table->date('ics_date');
                $table->string('fund_cluster')->nullable();
                $table->unsignedInteger('issued_to_id')->nullable();
                $table->unsignedInteger('issued_by_id')->nullable();
                $table->unsignedInteger('approved_by_id')->nullable();
                $table->unsignedTinyInteger('status_id');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('issued_to_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('issued_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('status_id')->references('id')->on('list_statuses');
            });

            Schema::create('inventory_ics_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ics_id')->constrained('inventory_ics')->cascadeOnDelete();
                $table->unsignedInteger('item_id');
                $table->string('unit_of_measure')->nullable();
                $table->decimal('quantity', 12, 2)->default(1);
                $table->decimal('unit_value', 10, 2)->nullable();
                $table->decimal('total_value', 10, 2)->nullable();
                $table->integer('estimated_useful_life')->nullable();
                $table->string('description')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            });
        }

        // PAR — Property Acknowledgment Receipt (non-expendable/equipment)
        if (!Schema::hasTable('inventory_par')) {
            Schema::create('inventory_par', function (Blueprint $table) {
                $table->id();
                $table->string('par_no')->unique();
                $table->date('par_date');
                $table->string('fund_cluster')->nullable();
                $table->unsignedInteger('received_by_id')->nullable();
                $table->unsignedInteger('issued_by_id')->nullable();
                $table->unsignedInteger('approved_by_id')->nullable();
                $table->unsignedTinyInteger('status_id');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('received_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('issued_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('status_id')->references('id')->on('list_statuses');
            });

            Schema::create('inventory_par_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('par_id')->constrained('inventory_par')->cascadeOnDelete();
                $table->unsignedInteger('item_id');
                $table->string('property_no')->nullable();
                $table->date('date_acquired')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->decimal('quantity', 12, 2)->default(1);
                $table->text('description')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_par_items');
        Schema::dropIfExists('inventory_par');
        Schema::dropIfExists('inventory_ics_items');
        Schema::dropIfExists('inventory_ics');
    }
};
