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

            $table->unsignedInteger('inventory_id')->index();
            $table->foreign('inventory_id')
                ->references('id')
                ->on('inventory_items');

            $table->unsignedInteger('requested_by_id')->index();
            $table->foreign('requested_by_id')
                ->references('id')
                ->on('users');

            $table->unsignedInteger('approved_by_id')->index()->nullablr();
            $table->foreign('approved_by_id')
                ->references('id')
                ->on('users');

            $table->unsignedTinyInteger('status_id')->index();
            $table->foreign('status_id')
                ->references('id')
                ->on('list_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_withdrawals');
    }
};
