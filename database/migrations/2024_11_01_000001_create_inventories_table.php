<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inventories')) {
            return;
        }

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('category_id');
            $table->unsignedTinyInteger('unit_id');
            $table->decimal('min_stock_level', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('list_dropdowns')
                ->onDelete('restrict');

            $table->foreign('unit_id')
                ->references('id')
                ->on('list_dropdowns')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventories');
    }
};


