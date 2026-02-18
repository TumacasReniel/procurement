<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procurement_code_units', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('procurement_code_id')->unsigned()->index();
            $table->foreign('procurement_code_id')->references('id')->on('procurement_codes');
            $table->tinyInteger('end_user_id')->unsigned()->index();
            $table->foreign('end_user_id')->references('id')->on('list_units');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_code_units');
    }
};
