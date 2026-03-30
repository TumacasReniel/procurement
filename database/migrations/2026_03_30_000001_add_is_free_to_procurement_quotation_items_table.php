<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_quotation_items', function (Blueprint $table) {
            $table->boolean('is_free')->default(false)->after('bid_price');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_quotation_items', function (Blueprint $table) {
            $table->dropColumn('is_free');
        });
    }
};
