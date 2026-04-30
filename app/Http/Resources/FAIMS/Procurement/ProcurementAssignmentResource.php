<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcurementAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'name' => $this->user?->profile?->full_name
                ?? $this->user?->profile?->fullname
                ?? $this->user?->name
                ?? 'User #' . $this->user_id,
            'user' => $this->whenLoaded('user'),
            'created_by_id' => $this->created_by_id,
            'created_by' => $this->whenLoaded('created_by'),
        ];
    }
}
