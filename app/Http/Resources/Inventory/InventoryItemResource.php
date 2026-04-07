<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'stock_id' => $this->stock_id,
            'stock_name' => $this->stock?->name ?? '-',
            'category_id' => $this->category_id,
            'category' => $this->category?->name ?? '-',
            'quantity' => (int) $this->quantity,
            'unit_cost' => (int) $this->unit_cost,
            'expiration' => optional($this->expiration)->format('Y-m-d'),
        ];
    }
}
