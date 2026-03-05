<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementCodeRequest extends FormRequest
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
        $rules = [
            'code' => 'required|string|max:255|unique:procurement_codes,code',
            'title' => 'required|string|max:1000',
            'allocated_budget' => 'required|numeric|min:0',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 10),
            'app_type_id' => 'required|exists:list_dropdowns,id',
            'mode_of_procurement_id' => 'required|exists:list_dropdowns,id',
            'end_user_ids' => 'required|array|min:1',
            'end_user_ids.*' => 'exists:list_dropdowns,id',
        ];

        // For updates, exclude the current record from unique validation
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $procurementCodeId = $this->route('procurement_code') ?? $this->input('id');
            $rules['code'] = 'required|string|max:255|unique:procurement_codes,code,' . $procurementCodeId;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The PAP code is required.',
            'code.unique' => 'This PAP code already exists.',
            'title.required' => 'The project description/title is required.',
            'allocated_budget.required' => 'The allocated budget is required.',
            'allocated_budget.numeric' => 'The allocated budget must be a valid number.',
            'allocated_budget.min' => 'The allocated budget must be greater than 0.',
            'year.required' => 'The year is required.',
            'year.integer' => 'The year must be a valid year.',
            'app_type_id.required' => 'Please select an APP type.',
            'app_type_id.exists' => 'The selected APP type is invalid.',
            'mode_of_procurement_id.required' => 'Please select a mode of procurement.',
            'mode_of_procurement_id.exists' => 'The selected mode of procurement is invalid.',
            'end_user_ids.required' => 'Please select at least one end user.',
            'end_user_ids.min' => 'Please select at least one end user.',
            'end_user_ids.*.exists' => 'One or more selected end users are invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'PAP Code',
            'title' => 'Project Description/Title',
            'allocated_budget' => 'Allocated Budget',
            'year' => 'Year',
            'app_type_id' => 'APP Type',
            'mode_of_procurement_id' => 'Mode of Procurement',
            'end_user_ids' => 'End Users',
        ];
    }
}
