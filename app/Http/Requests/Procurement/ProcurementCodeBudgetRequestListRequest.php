<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementCodeBudgetRequestListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'status' => 'nullable|in:all,pending,approved,rejected',
            'count' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'option' => 'nullable|string|in:lists',
        ];
    }
}
