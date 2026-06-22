<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->decimal('project_total_budget', 15, 2)->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn('project_total_budget');
        });
    }
};
