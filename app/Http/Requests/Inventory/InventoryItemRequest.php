<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    protected function prepareForValidation(): void
    {
        if ($this->category_id === '' || $this->category_id === '0' || $this->category_id === 0) {
            $this->merge(['category_id' => null]);
        }
    }

    public function rules(): array
    {
        $itemId = $this->resolveItemId();

        return [
            'code'          => ['nullable', 'string', 'max:255', Rule::unique('inventory_items', 'code')->ignore($itemId)],
            'name'          => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'name')->ignore($itemId)],
            'category_id'   => ['required', 'exists:list_dropdowns,id'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'        => 'This item code is already in use.',
            'name.required'      => 'Please enter the item name.',
            'name.unique'        => 'This item name is already in use.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists'   => 'The selected category is invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code'        => 'item code',
            'name'        => 'item name',
            'category_id' => 'category',
        ];
    }

    protected function resolveItemId(): mixed
    {
        $item = $this->route('inventory_item') ?? $this->input('id');

        return is_object($item) ? $item->id : $item;
    }
}
