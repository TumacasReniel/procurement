<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryReceivingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $procurement = $this->noa?->procurement;
        $supplier = $this->noa?->procurement_quotation?->supplier;
        $location = $this->place_of_delivery;

        return [
            'id' => $this->id,
            'po_number' => $this->code,
            'supplier_name' => $supplier?->name ?? '-',
            'remarks' => $procurement?->purpose ?: ($procurement?->title ?: '-'),
            'received_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
            'status' => $this->status?->name ?? 'Completed',
            'procurement_code' => $procurement?->code,
            'procurement_title' => $procurement?->title,
            'location_name' => $location?->name,
        ];
    }
}
