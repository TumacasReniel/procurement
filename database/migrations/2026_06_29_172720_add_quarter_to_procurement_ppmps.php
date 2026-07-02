<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->tinyInteger('quarter')->nullable()->after('is_current');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn('quarter');
        });
    }
};
