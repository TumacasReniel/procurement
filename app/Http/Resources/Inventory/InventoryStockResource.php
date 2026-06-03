<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'item_id'    => $this->item_id,
            'name'       => $this->item?->name ?? '—',
            'code'       => $this->item?->code ?? '—',
            'item_name'  => $this->item?->name ?? '—',
            'item_code'  => $this->item?->code ?? '—',
            'quantity'   => (float) $this->quantity,
            'unit_id'    => $this->unit_id,
            'unit'       => $this->unit?->name_short ?? '—',
            'unit_long'  => $this->unit?->name_long ?? '—',
            'unit_cost'   => (float) $this->unit_cost,
            'description' => $this->description,
            'entry_date'  => optional($this->created_at)->format('Y-m-d H:i:s'),
            'created_at'  => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
