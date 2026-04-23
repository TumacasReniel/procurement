<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryWithdrawalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'item_id' => $this->inventory_id,
            'item_name' => $this->item?->name ?? '-',
            'requested_by_id' => $this->requested_by_id,
            'requested_by' => $this->requestedBy?->profile?->fullname ?? $this->requestedBy?->username ?? '-',
            'approved_by_id' => $this->approved_by_id,
            'approved_by' => $this->approvedBy?->profile?->fullname ?? $this->approvedBy?->username ?? '-',
            'status_id' => $this->status_id,
            'status' => $this->status?->name ?? '-',
            'released_at' => $this->released_at?->format('Y-m-d H:i:s'),
            'remarks' => $this->remarks,
        ];
    }
}
