<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryStockRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    public function rules(): array
    {
        return [
            'item_id'         => ['required', 'exists:inventory_items,id'],
            'quantity'        => ['required', 'numeric', 'min:0'],
            'unit_id'         => ['required', 'exists:unit_types,id'],
            'unit_cost'       => ['nullable', 'numeric', 'min:0'],
            'description'     => ['nullable', 'string', 'max:500'],
            'expiration_date' => ['nullable', 'date'],
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
}
