<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stockId = $this->route('inventory_stock')?->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('inventory_stocks', 'code')->ignore($stockId)],
            'name' => ['required', 'string', 'max:255', Rule::unique('inventory_stocks', 'name')->ignore($stockId)],
            'entry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Code is required.',
            'code.unique' => 'This stock code is already in use.',
            'name.required' => 'Name is required.',
            'name.unique' => 'This stock name is already in use.',
        ];
    }
}
