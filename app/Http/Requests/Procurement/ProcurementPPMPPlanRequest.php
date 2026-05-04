<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementPPMPPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['nullable', 'integer', 'exists:list_units,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
            'plan_type' => ['required', 'in:annual,supplemental'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.required' => 'Please select a plan year.',
            'plan_type.required' => 'Please select the APP type.',
        ];
    }
}
