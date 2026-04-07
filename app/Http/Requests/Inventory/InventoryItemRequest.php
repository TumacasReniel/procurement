<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'stock_id' => ['required', 'exists:inventory_stocks,id'],
            'category_id' => ['required', 'exists:list_dropdowns,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['required', 'integer', 'min:0'],
            'expiration' => ['required', 'date'],
        ];
    }
}
