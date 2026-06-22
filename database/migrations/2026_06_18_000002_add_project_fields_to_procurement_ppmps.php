<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_ppmps', 'project_type')) {
                $table->string('project_type')->nullable()->after('attachment_original_name');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'recommended_mode_of_procurement')) {
                $table->string('recommended_mode_of_procurement')->nullable()->after('project_type');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'pre_procurement_conference')) {
                $table->string('pre_procurement_conference')->nullable()->after('recommended_mode_of_procurement');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'start_of_procurement_activity')) {
                $table->date('start_of_procurement_activity')->nullable()->after('pre_procurement_conference');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'end_of_procurement_activity')) {
                $table->date('end_of_procurement_activity')->nullable()->after('start_of_procurement_activity');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('end_of_procurement_activity');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'attached_supporting_documents')) {
                $table->string('attached_supporting_documents')->nullable()->after('expected_delivery_date');
            }
            if (! Schema::hasColumn('procurement_ppmps', 'remarks')) {
                $table->text('remarks')->nullable()->after('attached_supporting_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn([
                'project_type',
                'recommended_mode_of_procurement',
                'pre_procurement_conference',
                'start_of_procurement_activity',
                'end_of_procurement_activity',
                'expected_delivery_date',
                'attached_supporting_documents',
                'remarks',
            ]);
        });
    }
};
