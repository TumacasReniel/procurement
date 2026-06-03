<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryRisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'ris_no'                => $this->ris_no,
            'fund_cluster'          => $this->fund_cluster,
            'division'              => $this->division,
            'responsibility_center' => $this->responsibility_center,
            'purpose'               => $this->purpose,
            'ris_date'              => $this->ris_date?->format('Y-m-d'),
            'requested_by_id'       => $this->requested_by_id,
            'requested_by'          => $this->requestedBy?->profile?->fullname ?? $this->requestedBy?->username ?? '—',
            'approved_by_id'        => $this->approved_by_id,
            'approved_by'           => $this->approvedBy?->profile?->fullname ?? $this->approvedBy?->username ?? '—',
            'issued_by_id'          => $this->issued_by_id,
            'issued_by'             => $this->issuedBy?->profile?->fullname ?? $this->issuedBy?->username ?? '—',
            'received_by_id'        => $this->received_by_id,
            'received_by'           => $this->receivedBy?->profile?->fullname ?? $this->receivedBy?->username ?? '—',
            'status_id'             => $this->status_id,
            'status'                => $this->status?->name ?? '—',
            'items'                 => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($line) => [
                    'id'                => $line->id,
                    'item_id'           => $line->item_id,
                    'item_name'         => $line->item?->name ?? '—',
                    'item_code'         => $line->item?->code ?? '—',
                    'unit_of_issue'     => $line->unit_of_issue,
                    'quantity_requested'=> (float) $line->quantity_requested,
                    'quantity_issued'   => (float) $line->quantity_issued,
                    'remarks'           => $line->remarks,
                ]);
            }),
            'items_count'           => $this->whenCounted('items'),
            'created_at'            => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
