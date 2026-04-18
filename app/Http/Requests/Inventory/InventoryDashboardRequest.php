<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('period')) {
            $this->merge([
                'period' => strtolower(trim((string) $this->input('period'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::in(['monthly', 'quarterly', 'yearly'])],
        ];
    }
}
