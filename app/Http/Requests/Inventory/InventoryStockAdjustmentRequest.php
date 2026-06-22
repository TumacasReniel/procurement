<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryStockAdjustmentRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    public function rules(): array
    {
        return [
            'item_id'          => ['required', 'exists:inventory_items,id'],
            'stock_id'         => ['nullable', 'exists:inventory_stocks,id'],
            'type'             => ['required', Rule::in(['increase', 'decrease', 'correction'])],
            'quantity_adjusted'=> ['required', 'numeric', 'min:0.01'],
            'reason'           => ['required', 'string', 'max:500'],
            'remarks'          => ['nullable', 'string'],
            'adjusted_by_id'   => ['nullable', 'exists:users,id'],
            'approved_by_id'   => ['nullable', 'exists:users,id'],
            'status_id'        => ['required', 'exists:list_statuses,id'],
            'adjustment_date'  => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required'           => 'Please select the item to adjust.',
            'type.required'              => 'Please select the adjustment type.',
            'quantity_adjusted.required' => 'Please enter the adjustment quantity.',
            'quantity_adjusted.min'      => 'Quantity must be greater than zero.',
            'reason.required'            => 'Please provide a reason for the adjustment.',
            'adjustment_date.required'   => 'Please enter the adjustment date.',
        ];
    }
}
