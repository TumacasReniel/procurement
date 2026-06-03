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
        return [
            'item_id'     => ['required', 'exists:inventory_items,id'],
            'quantity'    => ['required', 'numeric', 'min:0'],
            'unit_id'     => ['required', 'exists:unit_types,id'],
            'unit_cost'   => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required'  => 'Please select an item.',
            'item_id.exists'    => 'The selected item is invalid.',
            'quantity.required' => 'Please enter the quantity.',
            'unit_id.required'  => 'Please select a unit.',
            'unit_id.exists'    => 'The selected unit is invalid.',
            'unit_cost.required'=> 'Please enter the unit cost.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id'   => 'item',
            'quantity'  => 'quantity',
            'unit_id'   => 'unit',
            'unit_cost' => 'unit cost',
        ];
    }

    protected function resolveStockId(): mixed
    {
        $stock = $this->route('inventory_stock') ?? $this->input('id');

        return is_object($stock) ? $stock->id : $stock;
    }
}
