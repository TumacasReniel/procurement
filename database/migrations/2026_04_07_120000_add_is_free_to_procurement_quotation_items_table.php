<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_quotation_items')) {
            return;
        }

        if (! Schema::hasColumn('procurement_quotation_items', 'is_free')) {
            Schema::table('procurement_quotation_items', function (Blueprint $table) {
                $table->boolean('is_free')->default(false)->after('status_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procurement_quotation_items')) {
            return;
        }

        if (Schema::hasColumn('procurement_quotation_items', 'is_free')) {
            Schema::table('procurement_quotation_items', function (Blueprint $table) {
                $table->dropColumn('is_free');
            });
        }
    }
};
