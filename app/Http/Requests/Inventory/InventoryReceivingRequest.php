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
}
