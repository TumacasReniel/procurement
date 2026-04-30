<?php

namespace App\Http\Requests\FAIMS\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ReceivingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option' => ['nullable', 'string'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'in:latest,oldest'],
        ];
    }
}
