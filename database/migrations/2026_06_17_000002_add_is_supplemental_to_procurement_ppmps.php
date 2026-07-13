<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('procurement_ppmps', 'is_supplemental')) {
            Schema::table('procurement_ppmps', function (Blueprint $table) {
                $table->boolean('is_supplemental')->default(false)->after('ppmp_type_version');
            });
        }

        // Backfill existing SPP records
        $sppAppTypeIds = DB::table('list_dropdowns')
            ->where('name', 'Supplemental Procurement Plan')
            ->pluck('id');

        DB::table('procurement_ppmps')
            ->where(function ($q) use ($sppAppTypeIds) {
                $q->where('title', 'Supplemental Procurement Plan')
                    ->orWhere('code', 'LIKE', 'SPP-%');

                if ($sppAppTypeIds->isNotEmpty()) {
                    $q->orWhereIn('reference_app_id', $sppAppTypeIds);
                }
            })
            ->update(['is_supplemental' => true]);
    }

    public function down(): void
    {
        Schema::table('procurement_ppmps', function (Blueprint $table) {
            $table->dropColumn('is_supplemental');
        });
    }
};
