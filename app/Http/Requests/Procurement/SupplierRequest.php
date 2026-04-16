<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = optional($this->route('supplier'))->id ?? $this->route('supplier') ?? $this->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplierId)],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('suppliers', 'code')->ignore($supplierId)],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'conformes' => ['nullable', 'array'],
            'conformes.*.name' => ['nullable', 'string', 'max:255'],
            'conformes.*.position' => ['nullable', 'string', 'max:255'],
            'conformes.*.contact_no' => ['nullable', 'string', 'max:50'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'This field is required',
            'name.unique' => 'Supplier name already exists',
            'code.unique' => 'Supplier code already exists',
        ];
    }
}
