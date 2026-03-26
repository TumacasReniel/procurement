<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Services\DropdownClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DashboardClass
{
    protected $dropdown;

    public function __construct(DropdownClass $dropdown)
    {
        $this->dropdown = $dropdown;
    }

    public function dashboard(array $filters = [])
    {
        $hasInventories = Schema::hasTable('inventories');
        $hasStocks = Schema::hasTable('inventory_stocks');

        [$period, $startDate, $endDate] = $this->resolveDateRange((string) ($filters['period'] ?? 'monthly'));

        $totalItems = $hasInventories ? Inventory::count() : 0;

        $lowStockItems = ($hasInventories && $hasStocks)
            ? Inventory::whereHas('stocks', function (Builder $q) use ($startDate, $endDate) {
                $this->applyDateRange($q, $startDate, $endDate, 'last_updated');
                $q->whereColumn('quantity', '<=', 'inventories.min_stock_level');
            })->count()
            : 0;

        $outOfStock = ($hasInventories && $hasStocks)
            ? Inventory::whereHas('stocks', function (Builder $q) use ($startDate, $endDate) {
                $this->applyDateRange($q, $startDate, $endDate, 'last_updated');
                $q->where('quantity', 0);
            })->count()
            : 0;

        $stockBaseQuery = InventoryStock::query();
        if ($hasStocks) {
            $this->applyDateRange($stockBaseQuery, $startDate, $endDate, 'last_updated');
        }

        $totalQuantity = $hasStocks ? (clone $stockBaseQuery)->sum('quantity') : 0;

        $byCategory = ($hasInventories && $hasStocks)
            ? Inventory::with('category')
                ->selectRaw('category_id, COUNT(*) as count, SUM(stocks.quantity) as total_qty')
                ->join('inventory_stocks as stocks', 'inventories.id', '=', 'stocks.inventory_id')
                ->whereBetween('stocks.last_updated', [$startDate, $endDate])
                ->groupBy('category_id')
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->category->name ?? 'Uncategorized',
                    'count' => (int) $item->count,
                    'y' => (float) ($item->total_qty ?? 0),
                ])
            : collect();

        $recent = $hasStocks
            ? (clone $stockBaseQuery)
                ->with(['inventory.category', 'inventory.unit'])
                ->orderBy('last_updated', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($stock) {
                    $itemName = $stock->inventory->name ?? '-';
                    $category = $stock->inventory->category->name ?? '-';
                    $unit = $stock->inventory->unit->name ?? '-';

                    return [
                        'id' => $stock->id,
                        'code' => 'STK-' . str_pad((string) $stock->id, 6, '0', STR_PAD_LEFT),
                        'stock_category' => $category,
                        'item_name' => $itemName,
                        'unit' => $unit,
                        'quantity' => (float) $stock->quantity,
                        'unit_cost' => $stock->unit_cost ?? null,
                        'expiration' => $stock->expiration_date ?? $stock->expiration ?? null,
                    ];
                })
                ->values()
            : collect();

        $byStatus = $hasStocks
            ? (clone $stockBaseQuery)
                ->groupBy('status')
                ->selectRaw('status, SUM(quantity) as total')
                ->pluck('total', 'status')
            : collect();

        $categories = $this->dropdown->dropdowns('Inventory Category');
        $units = $this->dropdown->dropdowns('Unit');
        $locations = $this->dropdown->dropdowns('Location');

        return compact(
            'totalItems',
            'lowStockItems',
            'outOfStock',
            'totalQuantity',
            'byCategory',
            'recent',
            'byStatus',
            'categories',
            'units',
            'locations',
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

    protected function applyDateRange(Builder $query, Carbon $start, Carbon $end, string $column = 'last_updated'): void
    {
        $query->whereBetween($column, [$start, $end]);
    }
}
