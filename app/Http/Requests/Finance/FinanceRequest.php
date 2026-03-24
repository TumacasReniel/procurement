<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class FinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'request_type_id' => $this->normalizeSelectValue($this->input('request_type_id')),
            'division_id' => $this->normalizeSelectValue($this->input('division_id')),
            'fund_source_id' => $this->normalizeSelectValue($this->input('fund_source_id')),
            'project_type_id' => $this->normalizeSelectValue($this->input('project_type_id')),
            'project_id' => $this->normalizeSelectValue($this->input('project_id')),
            'creditor_id' => $this->normalizeSelectValue($this->input('creditor_id')),
        ]);

        $particulars = $this->input('particulars');
        if (is_string($particulars)) {
            $particulars = trim($particulars);
        }

        if (empty($particulars) && is_string($this->input('purpose'))) {
            $particulars = trim($this->input('purpose'));
        }

        $this->merge([
            'particulars' => $particulars,
        ]);
    }

    private function normalizeSelectValue($value)
    {
        if (is_array($value)) {
            return $value['value'] ?? $value['id'] ?? $value['label'] ?? $value['name'] ?? null;
        }

        if (is_object($value)) {
            return $value->value ?? $value->id ?? $value->label ?? $value->name ?? null;
        }

        return $value;
    }

    public function rules(): array
    {
        return [
            'request_date' => 'required|date',
            'request_type_id' => 'required|exists:finance_request_types,id',
            'division_id' => 'required|exists:list_units,id',
            'fund_source_id' => 'required|exists:list_dropdowns,id',
            'project_type_id' => 'nullable|exists:list_dropdowns,id',
            'project_id' => 'nullable|exists:list_projects,id',
            'creditor_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'particulars' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'request_date.required' => 'Request date is required.',
            'request_type_id.required' => 'Request type is required.',
            'division_id.required' => 'Division is required.',
            'fund_source_id.required' => 'Fund source is required.',
            'fund_source_id.exists' => 'Fund source is invalid.',
            'project_type_id.exists' => 'Project type is invalid.',
            'creditor_id.required' => 'Payee / Creditor is required.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'particulars.string' => 'Particulars must be a valid text.',
            'particulars.max' => 'Particulars may not be greater than 1000 characters.',
        ];
    }
}
