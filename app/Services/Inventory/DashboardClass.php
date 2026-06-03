<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryReceiving;
use App\Models\InventoryStock;
use App\Models\InventoryWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardClass
{
    public function dashboard(array $filters = [])
    {
        $hasItems      = Schema::hasTable('inventory_items');
        $hasStocks     = Schema::hasTable('inventory_stocks');
        $hasReceivings = Schema::hasTable('inventory_receivings');
        $hasWithdrawals = Schema::hasTable('inventory_withdrawals');

        [$period, $startDate, $endDate] = $this->resolveDateRange((string) ($filters['period'] ?? 'monthly'));

        // Load items with aggregated stock quantities (filtered by created_at range)
        $allItems = $hasItems
            ? InventoryItem::with(['category:id,name'])
                ->withSum('stocks', 'quantity')
                ->withCount('stocks')
                ->whereBetween('inventory_items.created_at', [$startDate, $endDate])
                ->get()
            : collect();

        $totalItems    = $allItems->count();
        $totalQuantity = $allItems->sum('stocks_sum_quantity');
        $lowStockItems = $allItems->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) > 0 && ($i->stocks_sum_quantity ?? 0) <= 5)->count();
        $outOfStock    = $allItems->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= 0)->count();

        $totalStocks = $hasStocks
            ? InventoryStock::whereBetween('created_at', [$startDate, $endDate])->count()
            : 0;

        $receivingsCount = $hasReceivings
            ? InventoryReceiving::whereBetween('received_at', [$startDate, $endDate])->count()
            : 0;

        $withdrawalsCount = $hasWithdrawals
            ? InventoryWithdrawal::whereBetween('released_at', [$startDate, $endDate])->count()
            : 0;

        // Category breakdown — group items by category, sum their stock quantities
        $byCategory = $allItems
            ->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized')
            ->map(fn ($items, $name) => [
                'name'  => $name,
                'count' => $items->count(),
                'y'     => (float) $items->sum('stocks_sum_quantity'),
            ])
            ->values();

        // Recent items with stock summary
        $recent = $hasItems
            ? InventoryItem::with(['category:id,name'])
                ->withSum('stocks', 'quantity')
                ->withCount('stocks')
                ->withMax('stocks', 'unit_cost')
                ->whereBetween('inventory_items.created_at', [$startDate, $endDate])
                ->orderByDesc('inventory_items.created_at')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'id'             => $item->id,
                    'code'           => $item->code,
                    'item_name'      => $item->name,
                    'category'       => $item->category?->name ?? '—',
                    'total_quantity' => (float) ($item->stocks_sum_quantity ?? 0),
                    'unit_cost'      => (float) ($item->stocks_max_unit_cost ?? 0),
                    'stock_count'    => (int)  ($item->stocks_count ?? 0),
                ])
                ->values()
            : collect();

        $byStatus = [
            'In Stock'    => $allItems->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) > 5)->count(),
            'Low Stock'   => $lowStockItems,
            'Out of Stock' => $outOfStock,
        ];

        return compact(
            'totalItems',
            'lowStockItems',
            'outOfStock',
            'totalQuantity',
            'totalStocks',
            'receivingsCount',
            'withdrawalsCount',
            'byCategory',
            'recent',
            'byStatus',
            'period',
            'startDate',
            'endDate'
        ) + [
            'start_date' => $startDate->toDateString(),
            'end_date'   => $endDate->toDateString(),
        ];
    }

    protected function resolveDateRange(string $period): array
    {
        $normalized = strtolower(trim($period));

        $aliases = ['week' => 'weekly', 'month' => 'monthly', 'quarter' => 'quarterly', 'year' => 'yearly', 'annually' => 'yearly'];
        $normalized = $aliases[$normalized] ?? $normalized;

        if (!in_array($normalized, ['monthly', 'quarterly', 'yearly'], true)) {
            $normalized = 'monthly';
        }

        $now   = Carbon::now();
        $start = match ($normalized) {
            'quarterly' => $now->copy()->startOfQuarter(),
            'yearly'    => $now->copy()->startOfYear(),
            default     => $now->copy()->startOfMonth(),
        };

        return [$normalized, $start, $now->copy()->endOfDay()];
    }
}
