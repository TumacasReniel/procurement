<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_ppmp_items', 'supporting_document_path')) {
                $table->string('supporting_document_path')->nullable()->after('attached_supporting_documents');
            }

            if (!Schema::hasColumn('procurement_ppmp_items', 'supporting_document_original_name')) {
                $table->string('supporting_document_original_name')->nullable()->after('supporting_document_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_ppmp_items', 'supporting_document_original_name')) {
                $table->dropColumn('supporting_document_original_name');
            }

            if (Schema::hasColumn('procurement_ppmp_items', 'supporting_document_path')) {
                $table->dropColumn('supporting_document_path');
            }
        });
    }
};
