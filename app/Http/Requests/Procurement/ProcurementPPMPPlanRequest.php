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
                'quarter' => ['required', 'integer', 'in:1,2,3,4'],
                'attachment_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
                'requested_by_id' => ['nullable', 'integer', 'exists:users,id'],
            ];
        }

        if ($this->option === 'finalize_to_final_app') {
            return [
                'option' => ['required', 'in:finalize_to_final_app'],
                'app_id' => ['required', 'integer', 'exists:procurement_apps,id'],
            ];
        }

        $rules = [
            'option' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'integer', 'exists:list_units,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
            'plan_type' => ['required', 'in:APP,SPP'],
            'plan_phase' => ['nullable', 'in:indicative,final'],
        ];

        if ($this->plan_type === 'SPP') {
            $rules['unit_id'] = ['required', 'integer', 'exists:list_units,id'];
            $rules['attachment_file'] = ['required', 'file', 'mimes:pdf', 'max:10240'];
            $rules['requested_by_id'] = ['nullable', 'integer', 'exists:users,id'];
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
