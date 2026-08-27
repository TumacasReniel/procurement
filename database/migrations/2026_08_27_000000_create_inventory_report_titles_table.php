<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_report_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('category_id')->index();
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('list_dropdowns')->cascadeOnDelete();
            $table->unique(['category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_report_titles');
    }
};
