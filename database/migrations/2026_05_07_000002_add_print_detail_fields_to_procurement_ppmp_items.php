<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            $table->string('project_type')->nullable()->after('item_description');
            $table->string('recommended_mode_of_procurement')->nullable()->after('project_type');
            $table->date('end_of_procurement_activity')->nullable()->after('recommended_mode_of_procurement');
            $table->date('expected_delivery_date')->nullable()->after('end_of_procurement_activity');
            $table->string('attached_supporting_documents')->nullable()->after('expected_delivery_date');
            $table->text('remarks')->nullable()->after('attached_supporting_documents');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmp_items', function (Blueprint $table) {
            $table->dropColumn([
                'project_type',
                'recommended_mode_of_procurement',
                'end_of_procurement_activity',
                'expected_delivery_date',
                'attached_supporting_documents',
                'remarks',
            ]);
        });
    }
};
