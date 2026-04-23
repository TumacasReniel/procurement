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
        $itemId = $this->resolveItemId();

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

    public function messages(): array
    {
        return [
            'code.required' => 'Please enter the item code.',
            'code.unique' => 'This item code is already in use.',
            'name.required' => 'Please enter the item name.',
            'name.unique' => 'This item name is already in use.',
            'stock_id.required' => 'Please select a stock group.',
            'category_id.required' => 'Please select an item category.',
            'quantity.required' => 'Please enter the item quantity.',
            'unit_cost.required' => 'Please enter the unit cost.',
            'expiration.required' => 'Please enter the expiration date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'item code',
            'name' => 'item name',
            'stock_id' => 'stock group',
            'category_id' => 'category',
            'quantity' => 'quantity',
            'unit_cost' => 'unit cost',
            'expiration' => 'expiration date',
        ];
    }

    protected function resolveItemId(): mixed
    {
        $item = $this->route('inventory_item') ?? $this->input('id');

        return is_object($item) ? $item->id : $item;
    }
}
