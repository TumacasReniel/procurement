<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_id' => ['required', 'exists:inventory_items,id'],
            'requested_by_id' => ['required', 'exists:users,id'],
            'approved_by_id' => ['nullable', 'exists:users,id'],
            'status_id' => ['required', 'exists:list_statuses,id'],
            'released_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_id.required' => 'Please select the inventory item.',
            'inventory_id.exists' => 'The selected item is invalid.',
            'requested_by_id.required' => 'Please select the requester.',
            'requested_by_id.exists' => 'The selected requester is invalid.',
            'approved_by_id.exists' => 'The selected approver is invalid.',
            'status_id.required' => 'Please select the withdrawal status.',
            'status_id.exists' => 'The selected status is invalid.',
            'released_at.date' => 'Please enter a valid released date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inventory_id' => 'item',
            'requested_by_id' => 'requested by',
            'approved_by_id' => 'approved by',
            'status_id' => 'status',
            'released_at' => 'released date',
            'remarks' => 'remarks',
        ];
    }
}
