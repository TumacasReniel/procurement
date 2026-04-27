<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            if (!Schema::hasColumn('procurements', 'reference_app_id')) {
                $table->unsignedTinyInteger('reference_app_id')->nullable()->after('classification_id');
                $table->foreign('reference_app_id')->references('id')->on('list_dropdowns');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            if (Schema::hasColumn('procurements', 'reference_app_id')) {
                $table->dropForeign(['reference_app_id']);
                $table->dropColumn('reference_app_id');
            }
        });
    }
};
