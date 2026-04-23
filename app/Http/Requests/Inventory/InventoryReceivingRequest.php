<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryReceivingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'exists:inventory_items,id'],
            'approved_by_id' => ['nullable', 'exists:users,id'],
            'status_id' => ['required', 'exists:list_statuses,id'],
            'received_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Please select the received item.',
            'item_id.exists' => 'The selected item is invalid.',
            'approved_by_id.exists' => 'The selected approver is invalid.',
            'status_id.required' => 'Please select the receiving status.',
            'status_id.exists' => 'The selected status is invalid.',
            'received_at.date' => 'Please enter a valid received date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id' => 'item',
            'approved_by_id' => 'approved by',
            'status_id' => 'status',
            'received_at' => 'received date',
            'remarks' => 'remarks',
        ];
    }
}
