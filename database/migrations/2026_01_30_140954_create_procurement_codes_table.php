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
        Schema::create('procurement_codes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title',100);
            $table->string('code',20);  
            $table->integer('year'); 
            $table->decimal('allocated_budget', 10, 2);
            $table->tinyInteger('app_type_id')->unsigned()->index();
            $table->foreign('app_type_id')->references('id')->on('list_dropdowns')->onDelete('cascade');;
            $table->tinyInteger('mode_of_procurement_id')->unsigned()->index();
            $table->foreign('mode_of_procurement_id')->references('id')->on('list_dropdowns')->onDelete('cascade');;
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_codes');
    }
};
