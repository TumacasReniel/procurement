<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the "Report Category" list_dropdowns values used by the Inventory > Report tab
 * (e.g. COA Reports, Basic Reports). Idempotent — safe to re-run.
 */
class InventoryReportCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'COA Reports',
            'Basic Reports',
        ];

        foreach ($categories as $name) {
            DB::table('list_dropdowns')->updateOrInsert(
                [
                    'name' => $name,
                    'classification' => 'Report Category',
                ],
                [
                    'type' => 'n/a',
                    'color' => 'n/a',
                    'others' => 'n/a',
                    'is_active' => 1,
                ]
            );
        }
    }
}
