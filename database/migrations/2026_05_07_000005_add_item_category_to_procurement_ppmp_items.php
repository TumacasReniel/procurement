<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_ppmp_items', 'item_category_id')) {
                $table->unsignedTinyInteger('item_category_id')->nullable()->after('project_type');
                $table->foreign('item_category_id')
                    ->references('id')
                    ->on('list_dropdowns')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_ppmp_items', 'item_category_id')) {
                $table->dropForeign(['item_category_id']);
                $table->dropColumn('item_category_id');
            }
        });
    }
};
