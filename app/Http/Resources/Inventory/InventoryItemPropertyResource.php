<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemPropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cost = (float) $this->acquisition_cost;
        $rate = (float) $this->depreciation_rate;
        $yearsHeld = $this->acquisition_date ? now()->diffInDays($this->acquisition_date, true) / 365 : 0;
        $accumulatedDepreciation = min($cost, $cost * ($rate / 100) * $yearsHeld);
        $bookValue = max(0, $cost - $accumulatedDepreciation);

        return [
            'id'                 => $this->id,
            'inventory_item_id'  => $this->inventory_item_id,
            'item_id'            => $this->inventory_item_id,
            'item_name'          => $this->item?->name ?? '—',
            'item_code'          => $this->item?->code ?? '—',
            'property_code'      => $this->property_code,
            'model'              => $this->model,
            'serial_no'          => $this->serial_no,
            'acquisition_date'   => optional($this->acquisition_date)->format('Y-m-d'),
            'acquisition_cost'   => round($cost, 2),
            'depreciation_rate'  => round($rate, 2),
            'book_value'         => round($bookValue, 2),
            'status'             => $this->status,
            'remarks'            => $this->remarks,
            'created_at'         => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
