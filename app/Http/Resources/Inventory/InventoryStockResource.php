<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'inventory_name' => $this->inventory?->name ?? '-',
            'inventory_category' => $this->inventory?->category?->name ?? '-',
            'inventory_unit' => $this->inventory?->unit?->name ?? '-',
            'location_id' => $this->location_id,
            'location_name' => $this->location?->name ?? '-',
            'quantity' => (float) $this->quantity,
            'status' => $this->status,
            'last_updated' => $this->last_updated?->format('Y-m-d H:i:s'),
        ];
    }
}
