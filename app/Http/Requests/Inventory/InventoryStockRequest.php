<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
class InventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'inventory_id' => ['required', 'exists:inventories,id'],
            'entry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Code is required.',
            'name.required' => 'Name is required.',
            'inventory_id.required' => 'Please select an inventory item.',
        ];
    }
}
