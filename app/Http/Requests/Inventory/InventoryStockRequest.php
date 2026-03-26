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
        $isCreate = $this->isMethod('post');

        $rules = [
            'location_id' => ['nullable', 'exists:list_dropdowns,id'],
            'requested_by_id' => ['nullable', 'exists:users,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['available', 'low', 'out', 'reserved'])],
            'last_updated' => ['nullable', 'date'],
        ];

        if ($isCreate) {
            $rules['inventory_id'] = ['required'];

            if ($this->input('inventory_id') === '__NEW__') {
                $rules['new_inventory_name'] = ['required', 'string', 'max:255'];
                $rules['new_inventory_description'] = ['nullable', 'string'];
                $rules['new_category_id'] = ['required', 'exists:list_dropdowns,id'];
                $rules['new_unit_id'] = ['required', 'exists:list_dropdowns,id'];
                $rules['new_min_stock_level'] = ['nullable', 'numeric', 'min:0'];
            } else {
                $rules['inventory_id'][] = 'exists:inventories,id';
            }
        } else {
            $rules['inventory_id'] = ['required', 'exists:inventories,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'inventory_id.required' => 'Please select an inventory item.',
            'inventory_id.exists' => 'The selected inventory item is invalid.',
            'location_id.exists' => 'The selected location is invalid.',
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a valid number.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status is invalid.',
            'new_inventory_name.required' => 'Item name is required when creating a new item.',
            'new_category_id.required' => 'Category is required when creating a new item.',
            'new_unit_id.required' => 'Unit is required when creating a new item.',
        ];
    }
}
