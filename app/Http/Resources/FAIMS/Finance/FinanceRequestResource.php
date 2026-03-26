<?php

namespace App\Http\Resources\FAIMS\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceRequestResource extends JsonResource
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
            'code' => $this->code,
            'date' => $this->date ? (new \DateTime($this->date))->format('F j, Y') : null,
            'payee' => $this->payee ?? $this->requested_by ?? null,
            'amount' => $this->amount ? number_format($this->amount, 2) : null,
            'division' => $this->division?->name,
            'obligation_type' => $this->request_type?->name,
            'status' => [
                'name' => $this->status?->name ?? 'N/A',
                'bg' => $this->status?->bg ?? 'bg-soft-secondary text-secondary',
            ],
            'created_by' => $this->created_by?->profile?->full_name ?? $this->created_by_id ?? null,
        ];
    }
}

