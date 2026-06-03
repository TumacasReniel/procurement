<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'name'           => $this->name,
            'category_id'    => $this->category_id,
            'category'       => $this->category?->name ?? '—',
            'total_quantity' => (float) ($this->stocks_sum_quantity ?? $this->stocks?->sum('quantity') ?? 0),
            'stock_count'    => (int)  ($this->stocks_count ?? $this->stocks?->count() ?? 0),
        ];
    }
}
