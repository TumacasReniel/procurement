<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('procurement_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('procurement_id');
            $table->string('status', 100);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('procurement_id')->references('id')->on('procurements')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('created_by_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_assignments');
    }
};
