<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class InventoryIcsRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    public function rules(): array
    {
        return [
            'ics_date'            => ['required', 'date'],
            'fund_cluster'        => ['nullable', 'string', 'max:100'],
            'issued_to_id'        => ['nullable', 'exists:users,id'],
            'issued_by_id'        => ['nullable', 'exists:users,id'],
            'approved_by_id'      => ['nullable', 'exists:users,id'],
            'status_id'           => ['required', 'exists:list_statuses,id'],
            'remarks'             => ['nullable', 'string'],
            'items'               => ['sometimes', 'array'],
            'items.*.item_id'     => ['required', 'exists:inventory_items,id'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_value'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'ics_date.required'          => 'Please enter the ICS date.',
            'status_id.required'         => 'Please select a status.',
            'items.*.item_id.required'   => 'Each line must have an item.',
            'items.*.quantity.required'  => 'Each line must have a quantity.',
        ];
    }
}
