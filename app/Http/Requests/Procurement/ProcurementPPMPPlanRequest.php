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
        if ($this->option === 'create_ppmp') {
            return [
                'option' => ['required', 'in:create_ppmp'],
                'unit_id' => ['required', 'integer', 'exists:list_units,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
            ];
        }

        $rules = [
            'option' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'integer', 'exists:list_units,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
            'plan_type' => ['required', 'in:APP,SPP'],
        ];

        if ($this->plan_type === 'APP') {
            $rules['unit_id'] = ['required', 'integer', 'exists:list_units,id'];
            $rules['item_name'] = ['required', 'string', 'max:255'];
            $rules['item_description'] = ['required', 'string'];
            $rules['item_quantity'] = ['required', 'numeric', 'min:0.0001'];
            $rules['item_unit_type_id'] = ['required', 'integer', 'exists:unit_types,id'];
            $rules['item_unit_cost'] = ['required', 'numeric', 'min:0'];
            $rules['project_type'] = ['required', 'string', 'max:255'];
            $rules['item_category_id'] = ['required', 'integer', 'exists:list_dropdowns,id'];
            $rules['recommended_mode_of_procurement'] = ['required', 'string', 'max:255'];
            $rules['pre_procurement_conference'] = ['required', 'string', 'max:255'];
            $rules['end_of_procurement_activity'] = ['nullable', 'date'];
            $rules['expected_delivery_date'] = ['nullable', 'date'];
            $rules['attached_supporting_documents'] = ['nullable', 'string', 'max:255'];
            $rules['supporting_document_file'] = ['nullable', 'file', 'max:10240'];
            $rules['remarks'] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->plan_type === 'SPP') {
            $this->merge(['year' => date('Y')]);
        }
    }

    public function messages(): array
    {
        return [
            'year.required' => 'Please select a plan year.',
            'plan_type.required' => 'Please select the APP type.',
        ];
    }
}
