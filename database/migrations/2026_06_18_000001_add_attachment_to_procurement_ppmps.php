<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_ppmps', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('is_supplemental');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_original_name']);
        });
    }
};
