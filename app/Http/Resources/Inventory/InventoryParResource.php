<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryParResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'par_no'         => $this->par_no,
            'par_date'       => $this->par_date?->format('Y-m-d'),
            'fund_cluster'   => $this->fund_cluster,
            'received_by_id' => $this->received_by_id,
            'received_by'    => $this->receivedBy?->profile?->fullname ?? $this->receivedBy?->username ?? '—',
            'issued_by_id'   => $this->issued_by_id,
            'issued_by'      => $this->issuedBy?->profile?->fullname ?? $this->issuedBy?->username ?? '—',
            'approved_by_id' => $this->approved_by_id,
            'approved_by'    => $this->approvedBy?->profile?->fullname ?? $this->approvedBy?->username ?? '—',
            'status_id'      => $this->status_id,
            'status'         => $this->status?->name ?? '—',
            'remarks'        => $this->remarks,
            'items_count'    => $this->whenCounted('items'),
            'items'          => $this->whenLoaded('items', fn () => $this->items->map(fn ($line) => [
                'id'           => $line->id,
                'item_id'      => $line->item_id,
                'item_name'    => $line->item?->name ?? '—',
                'item_code'    => $line->item?->code ?? '—',
                'quantity'     => (float) $line->quantity,
                'amount'       => (float) ($line->amount ?? 0),
                'property_no'  => $line->property_no,
                'date_acquired'=> $line->date_acquired?->format('Y-m-d'),
                'description'  => $line->description,
                'remarks'      => $line->remarks,
            ])),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
