<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_disbursement_obligations', function (Blueprint $table) {
            $table->id();
            $table->string('os_number')->nullable();
            $table->string('dv_number')->nullable();
            $table->string('request_number')->nullable();
            $table->string('payee')->nullable();
            $table->string('fund_source')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_disbursement_obligations');
    }
};
