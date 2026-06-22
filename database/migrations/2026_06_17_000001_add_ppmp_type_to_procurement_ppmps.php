<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->string('ppmp_type', 20)->default('indicative')->after('sub_status_id');
            $table->unsignedSmallInteger('ppmp_type_version')->default(1)->after('ppmp_type');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn(['ppmp_type', 'ppmp_type_version']);
        });
    }
};
