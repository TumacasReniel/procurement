<?php

namespace Database\Seeders;

use App\Models\InventoryReportTitle;
use App\Models\ListDropdown;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds inventory_report_titles, scoped per Report Category, for the
 * Inventory > Report tab's Add Report modal. Idempotent — safe to re-run.
 *
 * data_source picks which dataset the report view/print pulls from:
 * items_received/withdrawn -> InventoryReceiving/InventoryWithdrawal rows;
 * stocks_received/withdrawn -> the same rows summarized at stock level.
 */
class InventoryReportTitleSeeder extends Seeder
{
    public function run(): void
    {
        $titlesByCategory = [
            'COA Reports' => [
                'Supplies and Materials Issued' => InventoryReportTitle::SOURCE_RIS_ISSUED,
                'Laboratory Chemicals and Materials Utilized' => InventoryReportTitle::SOURCE_RIS_ISSUED,
            ],
            'Basic Reports' => [
                'All Items Received' => InventoryReportTitle::SOURCE_ITEMS_RECEIVED,
                'All Items Withdrawn' => InventoryReportTitle::SOURCE_ITEMS_WITHDRAWN,
                'All Stocks Received' => InventoryReportTitle::SOURCE_STOCKS_RECEIVED,
                'All Stocks Withdrawn' => InventoryReportTitle::SOURCE_STOCKS_WITHDRAWN,
                'Stocks Classification Received' => InventoryReportTitle::SOURCE_STOCKS_RECEIVED,
                'Stocks Classification Withdrawn' => InventoryReportTitle::SOURCE_STOCKS_WITHDRAWN,
                'By Customer Withdrawn' => InventoryReportTitle::SOURCE_ITEMS_WITHDRAWN,
                'By Supplier Receiving' => InventoryReportTitle::SOURCE_ITEMS_RECEIVED,
                'By Functional Unit Received' => InventoryReportTitle::SOURCE_ITEMS_RECEIVED,
                'By Functional Unit Withdrawal' => InventoryReportTitle::SOURCE_ITEMS_WITHDRAWN,
            ],
        ];

        foreach ($titlesByCategory as $categoryName => $titles) {
            $categoryId = ListDropdown::where('classification', 'Report Category')
                ->where('name', $categoryName)
                ->value('id');

            if (! $categoryId) {
                continue;
            }

            foreach ($titles as $name => $dataSource) {
                DB::table('inventory_report_titles')->updateOrInsert(
                    ['category_id' => $categoryId, 'name' => $name],
                    ['data_source' => $dataSource, 'is_active' => 1, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}
