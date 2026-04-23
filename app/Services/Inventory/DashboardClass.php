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
        $hasItems = Schema::hasTable('inventory_items');
        $hasStocks = Schema::hasTable('inventory_stocks');
        $hasReceivings = Schema::hasTable('inventory_receivings');
        $hasWithdrawals = Schema::hasTable('inventory_withdrawals');

        [$period, $startDate, $endDate] = $this->resolveDateRange((string) ($filters['period'] ?? 'monthly'));

        $itemBaseQuery = $hasItems ? InventoryItem::query() : null;
        $stockBaseQuery = $hasStocks ? InventoryStock::query() : null;

        if ($itemBaseQuery) {
            $this->applyDateRange($itemBaseQuery, $startDate, $endDate);
        }

        if ($stockBaseQuery) {
            $this->applyDateRange($stockBaseQuery, $startDate, $endDate, 'entry_date');
        }

        $totalItems = $hasItems ? (clone $itemBaseQuery)->count() : 0;
        $totalQuantity = $hasItems ? (clone $itemBaseQuery)->sum('quantity') : 0;
        $lowStockItems = $hasItems ? (clone $itemBaseQuery)->whereBetween('quantity', [1, 5])->count() : 0;
        $outOfStock = $hasItems ? (clone $itemBaseQuery)->where('quantity', '<=', 0)->count() : 0;
        $totalStocks = $hasStocks ? (clone $stockBaseQuery)->count() : 0;
        $receivingsCount = $hasReceivings
            ? InventoryReceiving::query()->whereBetween('received_at', [$startDate, $endDate])->count()
            : 0;
        $withdrawalsCount = $hasWithdrawals
            ? InventoryWithdrawal::query()->whereBetween('released_at', [$startDate, $endDate])->count()
            : 0;

        $byCategory = $hasItems
            ? InventoryItem::with('category')
                ->selectRaw('category_id, COUNT(*) as count, SUM(quantity) as total_qty')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('category_id')
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->category->name ?? 'Uncategorized',
                    'count' => (int) $item->count,
                    'y' => (float) ($item->total_qty ?? 0),
                ])
            : collect();

        $recent = $hasItems
            ? InventoryItem::with(['stock:id,code,name', 'category:id,name'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'stock_name' => $item->stock?->name ?? '-',
                        'stock_code' => $item->stock?->code ?? '-',
                        'stock_category' => $item->category?->name ?? '-',
                        'item_name' => $item->name,
                        'quantity' => (int) $item->quantity,
                        'unit_cost' => (float) ($item->unit_cost ?? 0),
                        'expiration' => optional($item->expiration)->format('M d, Y') ?? '-',
                    ];
                })
                ->values()
            : collect();

        $byStatus = [
            'In Stock' => $hasItems ? InventoryItem::query()->whereBetween('created_at', [$startDate, $endDate])->where('quantity', '>', 5)->count() : 0,
            'Low Stock' => $lowStockItems,
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
            'end_date' => $endDate->toDateString(),
        ];
    }

    protected function resolveDateRange(string $period): array
    {
        $normalized = strtolower(trim($period));
        if ($normalized === 'month') {
            $normalized = 'monthly';
        } elseif ($normalized === 'quarter') {
            $normalized = 'quarterly';
        } elseif ($normalized === 'year') {
            $normalized = 'yearly';
        }

        if (!in_array($normalized, ['monthly', 'quarterly', 'yearly'], true)) {
            $normalized = 'monthly';
        }

        $now = Carbon::now();
        $start = match ($normalized) {
            'quarterly' => $now->copy()->startOfQuarter(),
            'yearly' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        $end = $now->copy()->endOfDay();

        return [$normalized, $start, $end];
    }

    protected function applyDateRange($query, Carbon $start, Carbon $end, string $column = 'created_at'): void
    {
        $query->whereBetween($column, [$start, $end]);
    }
}
