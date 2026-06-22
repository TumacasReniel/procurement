<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementPPMPPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        if ($this->option === 'create_ppmp') {
            return [
                'option' => ['required', 'in:create_ppmp'],
                'unit_id' => ['required', 'integer', 'exists:list_units,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
                'attachment_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ];
        }

        $rules = [
            'option' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'integer', 'exists:list_units,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
            'plan_type' => ['required', 'in:APP,SPP'],
        ];

        if ($this->plan_type === 'SPP') {
            $rules['unit_id'] = ['required', 'integer', 'exists:list_units,id'];
            $rules['attachment_file'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
    }

    public function messages(): array
    {
        return [
            'year.required' => 'Please select a plan year.',
            'plan_type.required' => 'Please select the APP type.',
        ];
    }
}
