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
}
