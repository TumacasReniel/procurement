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
        $approval_status = $this->approval_status ?: 'Approved';
        $can_approve = $request->user()
            && $approval_status === 'Pending Approval'
            && (
                $request->user()->hasRole('Procurement Officer')
                || $request->user()->hasRole('Administrator')
            );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'tin' => $this->tin,
            'is_active' => $this->is_active,
            'approval_status' => $approval_status,
            'approved_by' => $this->approved_by?->profile?->full_name,
            'approved_by_id' => $this->approved_by_id,
            'approved_at' => $this->approved_at,
            'can_approve' => $can_approve,
            'status' => $approval_status === 'Pending Approval'
                ? [
                    'name' => 'Pending Approval',
                    'bg' => 'bg-warning text-dark',
                ]
                : [
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
