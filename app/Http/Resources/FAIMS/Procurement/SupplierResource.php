<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'status' => [
                'name' => $this->is_active ? 'Active' : 'Inactive',
                'bg' => $this->is_active ? 'bg-success' : 'bg-secondary',
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by?->profile?->full_name ?? 'System',
            'created_by_id' => $this->user_id,
            'address' => $this->address?->address,
            'conformes_count' => $this->conformes_count ?? ($this->conformes?->count() ?? 0),
            'attachments_count' => $this->attachments_count ?? ($this->attachments?->count() ?? 0),
            'conformes' => $this->conformes,
            'attachments' => $this->attachments,
        ];
    }
}
