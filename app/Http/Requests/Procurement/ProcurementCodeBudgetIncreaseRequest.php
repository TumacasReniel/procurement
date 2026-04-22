<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementCodeBudgetIncreaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The additional budget amount is required.',
            'amount.numeric' => 'The additional budget amount must be a valid number.',
            'amount.min' => 'The additional budget amount must be greater than zero.',
            'description.required' => 'Please provide a short justification for this budget request.',
            'description.max' => 'The justification may not be greater than 1000 characters.',
        ];
    }
}
