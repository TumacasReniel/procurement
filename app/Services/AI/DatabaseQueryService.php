<?php

namespace App\Services\AI;

use App\Models\InventoryItem;
use App\Models\InventoryRis;
use App\Models\InventoryReceiving;
use App\Models\InventoryStock;
use App\Models\InventoryWithdrawal;
use Illuminate\Support\Facades\Schema;

class DatabaseQueryService
{
    public function inventorySummary(): array
    {
        if (!Schema::hasTable('inventory_items')) {
            return ['error' => 'Inventory table not found'];
        }

        $items = InventoryItem::withSum('stocks', 'quantity')->get();

        return [
            'total_items'        => $items->count(),
            'total_qty_on_hand'  => round($items->sum('stocks_sum_quantity'), 2),
            'out_of_stock'       => $items->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= 0)->count(),
            'low_stock'          => $items->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) > 0 && ($i->stocks_sum_quantity ?? 0) <= 5)->count(),
            'total_receivings'   => Schema::hasTable('inventory_receivings') ? InventoryReceiving::count() : 0,
            'total_withdrawals'  => Schema::hasTable('inventory_withdrawals') ? InventoryWithdrawal::count() : 0,
            'total_ris'          => Schema::hasTable('inventory_ris') ? InventoryRis::count() : 0,
        ];
    }

    public function searchItems(string $keyword = '', int $limit = 10): array
    {
        if (!Schema::hasTable('inventory_items')) return [];

        return InventoryItem::with('category')
            ->withSum('stocks', 'quantity')
            ->withCount('stocks')
            ->when($keyword, fn ($q) =>
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%")
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$keyword}%"))
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'code'          => $i->code,
                'name'          => $i->name,
                'category'      => $i->category?->name ?? '—',
                'qty_on_hand'   => (float) ($i->stocks_sum_quantity ?? 0),
                'stock_entries' => (int) ($i->stocks_count ?? 0),
            ])
            ->toArray();
    }

    public function searchStocks(string $keyword = '', int $limit = 10): array
    {
        if (!Schema::hasTable('inventory_stocks')) return [];

        return InventoryStock::with(['item', 'unit'])
            ->when($keyword, fn ($q) =>
                $q->whereHas('item', fn ($i) =>
                    $i->where('name', 'like', "%{$keyword}%")
                      ->orWhere('code', 'like', "%{$keyword}%")
                )
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'item_name'   => $s->item?->name ?? '—',
                'item_code'   => $s->item?->code ?? '—',
                'quantity'    => (float) $s->quantity,
                'unit'        => $s->unit?->name_short ?? '—',
                'unit_cost'   => (float) $s->unit_cost,
                'total_value' => round((float) $s->quantity * (float) $s->unit_cost, 2),
                'description' => $s->description,
                'date_added'  => optional($s->created_at)->format('Y-m-d'),
            ])
            ->toArray();
    }

    public function searchReceivings(string $keyword = '', int $limit = 10): array
    {
        if (!Schema::hasTable('inventory_receivings')) return [];

        return InventoryReceiving::with(['item', 'status'])
            ->when($keyword, fn ($q) =>
                $q->whereHas('item', fn ($i) => $i->where('name', 'like', "%{$keyword}%"))
                  ->orWhere('remarks', 'like', "%{$keyword}%")
            )
            ->orderByDesc('received_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'item_name'   => $r->item?->name ?? '—',
                'status'      => $r->status?->name ?? '—',
                'received_at' => $r->received_at,
                'remarks'     => $r->remarks,
            ])
            ->toArray();
    }

    public function searchWithdrawals(string $keyword = '', int $limit = 10): array
    {
        if (!Schema::hasTable('inventory_withdrawals')) return [];

        return InventoryWithdrawal::with(['item', 'status'])
            ->when($keyword, fn ($q) =>
                $q->whereHas('item', fn ($i) => $i->where('name', 'like', "%{$keyword}%"))
                  ->orWhere('remarks', 'like', "%{$keyword}%")
            )
            ->orderByDesc('released_at')
            ->limit($limit)
            ->get()
            ->map(fn ($w) => [
                'item_name'   => $w->item?->name ?? '—',
                'status'      => $w->status?->name ?? '—',
                'released_at' => $w->released_at,
                'remarks'     => $w->remarks,
            ])
            ->toArray();
    }

    public function searchRis(string $keyword = '', int $limit = 10): array
    {
        if (!Schema::hasTable('inventory_ris')) return [];

        return InventoryRis::with('status')
            ->when($keyword, fn ($q) =>
                $q->where('ris_no', 'like', "%{$keyword}%")
                  ->orWhere('purpose', 'like', "%{$keyword}%")
                  ->orWhere('division', 'like', "%{$keyword}%")
            )
            ->orderByDesc('ris_date')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'ris_no'   => $r->ris_no,
                'date'     => $r->ris_date,
                'division' => $r->division,
                'purpose'  => $r->purpose,
                'status'   => $r->status?->name ?? '—',
                'items'    => $r->items()->count(),
            ])
            ->toArray();
    }

    public function procurementSummary(): array
    {
        if (!Schema::hasTable('procurements')) {
            return ['error' => 'No procurement data available'];
        }

        $total    = \App\Models\Procurement::count();
        $byStatus = \App\Models\Procurement::with('status')
            ->get()
            ->groupBy(fn ($p) => $p->status?->name ?? 'Unknown')
            ->map->count()
            ->toArray();

        return compact('total', 'byStatus');
    }

    public function searchProcurements(string $keyword = '', ?string $status = null, int $limit = 10): array
    {
        if (!Schema::hasTable('procurements')) return [];

        return \App\Models\Procurement::with('status')
            ->when($keyword, fn ($q) =>
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%")
                  ->orWhere('purpose', 'like', "%{$keyword}%")
            )
            ->when($status, fn ($q) =>
                $q->whereHas('status', fn ($s) => $s->where('name', $status))
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'code'    => $p->code,
                'title'   => $p->title,
                'purpose' => $p->purpose,
                'status'  => $p->status?->name ?? '—',
                'date'    => $p->date,
            ])
            ->toArray();
    }

    public function lowStockItems(float $threshold = 5): array
    {
        if (!Schema::hasTable('inventory_items')) return [];

        return InventoryItem::with('category')
            ->withSum('stocks', 'quantity')
            ->get()
            ->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= $threshold)
            ->map(fn ($i) => [
                'code'     => $i->code,
                'name'     => $i->name,
                'category' => $i->category?->name ?? '—',
                'qty'      => (float) ($i->stocks_sum_quantity ?? 0),
            ])
            ->values()
            ->toArray();
    }
}
