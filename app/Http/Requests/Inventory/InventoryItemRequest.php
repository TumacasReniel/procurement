<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('inventory_item')?->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'code')->ignore($itemId)],
            'name' => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'name')->ignore($itemId)],
            'stock_id' => ['required', 'exists:inventory_stocks,id'],
            'category_id' => ['required', 'exists:list_dropdowns,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['required', 'integer', 'min:0'],
            'expiration' => ['required', 'date'],
        ];
    }
}
