<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryWithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'requested_by' => $this->requested_by?->profile?->fullname ?? 'System',
            'item_name' => $this->item_name,
            'quantity' => (float) $this->quantity,
            'released_at' => $this->released_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'location_name' => $this->location?->name,
            'remarks' => $this->remarks,
        ];
    }
}
