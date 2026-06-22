<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryIcsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'ics_no'         => $this->ics_no,
            'ics_date'       => $this->ics_date?->format('Y-m-d'),
            'fund_cluster'   => $this->fund_cluster,
            'issued_to_id'   => $this->issued_to_id,
            'issued_to'      => $this->issuedTo?->profile?->fullname ?? $this->issuedTo?->username ?? '—',
            'issued_by_id'   => $this->issued_by_id,
            'issued_by'      => $this->issuedBy?->profile?->fullname ?? $this->issuedBy?->username ?? '—',
            'approved_by_id' => $this->approved_by_id,
            'approved_by'    => $this->approvedBy?->profile?->fullname ?? $this->approvedBy?->username ?? '—',
            'status_id'      => $this->status_id,
            'status'         => $this->status?->name ?? '—',
            'remarks'        => $this->remarks,
            'items_count'    => $this->whenCounted('items'),
            'items'          => $this->whenLoaded('items', fn () => $this->items->map(fn ($line) => [
                'id'         => $line->id,
                'item_id'    => $line->item_id,
                'item_name'  => $line->item?->name ?? '—',
                'item_code'  => $line->item?->code ?? '—',
                'quantity'   => (float) $line->quantity,
                'unit_value' => (float) ($line->unit_value ?? 0),
                'total_value'=> (float) ($line->total_value ?? 0),
                'unit_of_measure'          => $line->unit_of_measure,
                'estimated_useful_life'    => $line->estimated_useful_life,
                'description'              => $line->description,
                'remarks'                  => $line->remarks,
            ])),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
