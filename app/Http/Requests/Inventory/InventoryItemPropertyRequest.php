<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemPropertyRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    public function rules(): array
    {
        $propertyId = $this->route('inventory_item_property');

        return [
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'property_code'     => ['nullable', 'string', 'max:50', Rule::unique('inventory_item_properties', 'property_code')->ignore($propertyId)],
            'model'             => ['required', 'string', 'max:255'],
            'serial_no'         => ['required', 'string', 'max:255'],
            'acquisition_date'  => ['required', 'date'],
            'acquisition_cost'  => ['required', 'numeric', 'min:0'],
            'depreciation_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status'            => ['required', Rule::in(['active', 'inactive'])],
            'remarks'           => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select the inventory item this property belongs to.',
            'inventory_item_id.exists'   => 'The selected item is invalid.',
            'model.required'             => 'Please enter the model.',
            'serial_no.required'         => 'Please enter the serial number.',
            'acquisition_date.required'  => 'Please enter the acquisition date.',
            'acquisition_cost.required'  => 'Please enter the acquisition cost.',
            'depreciation_rate.required' => 'Please enter the depreciation rate.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inventory_item_id' => 'item',
            'serial_no'         => 'serial number',
            'acquisition_date'  => 'acquisition date',
            'acquisition_cost'  => 'acquisition cost',
            'depreciation_rate' => 'depreciation rate',
        ];
    }
}
