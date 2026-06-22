<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class InventoryPhysicalCountRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'count_date'    => ['required', 'date'],
                'counted_by_id' => ['nullable', 'exists:users,id'],
                'remarks'       => ['nullable', 'string'],
                'status_id'     => ['required', 'exists:list_statuses,id'],
            ];
        }

        return [
            'count_date'                        => ['sometimes', 'date'],
            'verified_by_id'                    => ['nullable', 'exists:users,id'],
            'approved_by_id'                    => ['nullable', 'exists:users,id'],
            'remarks'                           => ['nullable', 'string'],
            'status_id'                         => ['sometimes', 'exists:list_statuses,id'],
            'items'                             => ['sometimes', 'array'],
            'items.*.id'                        => ['required_with:items', 'exists:inventory_physical_count_items,id'],
            'items.*.physical_quantity'         => ['required_with:items', 'numeric', 'min:0'],
            'items.*.remarks'                   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'count_date.required'                      => 'Please enter the count date.',
            'status_id.required'                       => 'Please select a status.',
            'items.*.id.required_with'                 => 'Each item line must have a valid ID.',
            'items.*.physical_quantity.required_with'  => 'Each item must have a physical quantity.',
        ];
    }
}
