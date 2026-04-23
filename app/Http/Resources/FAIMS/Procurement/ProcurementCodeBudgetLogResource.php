<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProcurementCodeBudgetLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeLabel = match ($this->type) {
            'approval_deduction' => 'Budget Deduction',
            'budget_increase' => 'Budget Increase',
            default => Str::headline($this->type),
        };

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $typeLabel,
            'status' => $this->status,
            'status_label' => Str::headline($this->status ?: 'approved'),
            'amount' => $this->amount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'attachment_name' => $this->attachment_name,
            'attachment_url' => $this->attachment_path ? asset('storage/' . ltrim($this->attachment_path, '/')) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'amount_direction' => $this->type === 'approval_deduction' ? 'decrease' : 'increase',
            'procurement' => $this->procurement ? [
                'id' => $this->procurement->id,
                'code' => $this->procurement->code,
                'title' => $this->procurement->title,
                'status' => $this->procurement->status?->name,
            ] : null,
            'processed_by' => $this->processed_by ? [
                'id' => $this->processed_by->id,
                'name' => $this->processed_by->profile?->full_name
                    ?? $this->processed_by->profile?->fullname
                    ?? $this->processed_by->name,
            ] : null,
            'requested_by' => $this->requested_by ? [
                'id' => $this->requested_by->id,
                'name' => $this->requested_by->profile?->full_name
                    ?? $this->requested_by->profile?->fullname
                    ?? $this->requested_by->name,
            ] : null,
            'reviewed_by' => $this->reviewed_by ? [
                'id' => $this->reviewed_by->id,
                'name' => $this->reviewed_by->profile?->full_name
                    ?? $this->reviewed_by->profile?->fullname
                    ?? $this->reviewed_by->name,
            ] : null,
        ];
    }
}
