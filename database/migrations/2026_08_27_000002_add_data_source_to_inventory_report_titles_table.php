<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_report_titles', function (Blueprint $table) {
            $table->string('data_source')->default('none')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_report_titles', function (Blueprint $table) {
            $table->dropColumn('data_source');
        });
    }
};
