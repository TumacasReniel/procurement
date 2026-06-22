<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_items', 'category_id')) {
            return;
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->unsignedTinyInteger('category_id')->nullable()->default(null)->change();
            $table->foreign('category_id')->references('id')->on('list_dropdowns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('inventory_items', 'category_id')) {
            return;
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->unsignedTinyInteger('category_id')->nullable(false)->change();
            $table->foreign('category_id')->references('id')->on('list_dropdowns')->nullOnDelete();
        });
    }
};
