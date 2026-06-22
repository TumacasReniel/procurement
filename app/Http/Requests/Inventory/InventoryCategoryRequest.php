<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('list_dropdowns', 'name')
                    ->where('classification', 'Item Category')
                    ->ignore($id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
