<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryReceiving;
use App\Models\InventoryStock;
use App\Models\InventoryWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DashboardClass
{
    public function dashboard(array $filters = [])
    {
        $hasItems      = Schema::hasTable('inventory_items');
        $hasStocks     = Schema::hasTable('inventory_stocks');
        $hasReceivings = Schema::hasTable('inventory_receivings');
        $hasWithdrawals = Schema::hasTable('inventory_withdrawals');

        [$period, $startDate, $endDate] = $this->resolveDateRange((string) ($filters['period'] ?? 'monthly'));

        // All items (no date filter) — used for KPI counts and status breakdown
        $allItems = $hasItems
            ? InventoryItem::with(['category:id,name'])
                ->withSum('stocks', 'quantity')
                ->withCount('stocks')
                ->get()
            : collect();

        $totalItems    = $allItems->count();
        $totalQuantity = $allItems->sum('stocks_sum_quantity');

        // Low stock: total quantity is at or below the item's reorder_level (or <= 5 as fallback)
        $lowStockItems = $allItems->filter(function ($i) {
            $qty      = (float) ($i->stocks_sum_quantity ?? 0);
            $reorder  = (float) ($i->reorder_level ?? 0);
            $threshold = $reorder > 0 ? $reorder : 5;
            return $qty > 0 && $qty <= $threshold;
        })->count();

        $outOfStock = $allItems->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= 0)->count();

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

        // Recent items — within selected period, ordered newest first
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
            'In Stock'     => $allItems->filter(function ($i) {
                $qty      = (float) ($i->stocks_sum_quantity ?? 0);
                $reorder  = (float) ($i->reorder_level ?? 0);
                $threshold = $reorder > 0 ? $reorder : 5;
                return $qty > $threshold;
            })->count(),
            'Low Stock'    => $lowStockItems,
            'Out of Stock' => $outOfStock,
        ];

        // Items at or below reorder level — actionable alert list
        $reorderAlerts = $hasItems
            ? InventoryItem::withSum('stocks', 'quantity')
                ->where('reorder_level', '>', 0)
                ->havingRaw('COALESCE(stocks_sum_quantity, 0) <= reorder_level')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'reorder_level'])
                ->map(fn ($i) => [
                    'id'            => $i->id,
                    'code'          => $i->code,
                    'name'          => $i->name,
                    'reorder_level' => (float) $i->reorder_level,
                    'current_stock' => (float) ($i->stocks_sum_quantity ?? 0),
                ])
                ->values()
            : collect();

        // Stock entries expiring within 90 days
        $expiryAlerts = $hasStocks && Schema::hasColumn('inventory_stocks', 'expiration_date')
            ? InventoryStock::with('item:id,code,name')
                ->whereNotNull('expiration_date')
                ->where('quantity', '>', 0)
                ->whereDate('expiration_date', '<=', Carbon::now()->addDays(90))
                ->orderBy('expiration_date')
                ->get()
                ->map(fn ($s) => [
                    'item_id'         => $s->item_id,
                    'item_name'       => $s->item?->name ?? '—',
                    'item_code'       => $s->item?->code ?? '—',
                    'quantity'        => (float) $s->quantity,
                    'expiration_date' => $s->expiration_date?->toDateString(),
                    'days_remaining'  => (int) Carbon::now()->diffInDays($s->expiration_date, false),
                ])
                ->values()
            : collect();

        $filters = compact('period', 'startDate', 'endDate');

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
            'reorderAlerts',
            'expiryAlerts',
            'filters'
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
