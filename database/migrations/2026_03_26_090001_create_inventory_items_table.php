<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items')) return;

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->timestamps();
            $table->tinyInteger('category_id')->unsigned()->index()->nullable();
            $table->foreign('category_id')->references('id')->on('list_dropdowns')->onDelete('set null');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
