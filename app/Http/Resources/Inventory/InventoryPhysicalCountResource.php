<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryPhysicalCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'count_no'       => $this->count_no,
            'count_date'     => $this->count_date?->format('Y-m-d'),
            'counted_by_id'  => $this->counted_by_id,
            'counted_by'     => $this->countedBy?->profile?->fullname ?? $this->countedBy?->username ?? '—',
            'verified_by_id' => $this->verified_by_id,
            'verified_by'    => $this->verifiedBy?->profile?->fullname ?? $this->verifiedBy?->username ?? '—',
            'approved_by_id' => $this->approved_by_id,
            'approved_by'    => $this->approvedBy?->profile?->fullname ?? $this->approvedBy?->username ?? '—',
            'status_id'      => $this->status_id,
            'status'         => $this->status?->name ?? '—',
            'remarks'        => $this->remarks,
            'items_count'    => $this->whenCounted('items'),
            'items'          => $this->whenLoaded('items', fn () => $this->items->map(fn ($line) => [
                'id'                => $line->id,
                'item_id'           => $line->item_id,
                'item_name'         => $line->item?->name ?? '—',
                'item_code'         => $line->item?->code ?? '—',
                'system_quantity'   => (float) $line->system_quantity,
                'physical_quantity' => (float) $line->physical_quantity,
                'variance'          => (float) $line->physical_quantity - (float) $line->system_quantity,
                'remarks'           => $line->remarks,
            ])),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
