<?php

namespace App\Http\Requests\Procurement;

use App\Models\ProcurementCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

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
            'request_type' => ['required', Rule::in(['additional_budget', 'realignment'])],
            'source_procurement_code_id' => [
                Rule::requiredIf($this->input('request_type') === 'realignment'),
                'nullable',
                'integer',
                'exists:procurement_codes,id',
            ],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
            'attachment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
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
            'request_type.required' => 'Please select the budget request type.',
            'request_type.in' => 'Please select a valid budget request type.',
            'source_procurement_code_id.required' => 'Please select the PAP code where the realigned budget will come from.',
            'source_procurement_code_id.exists' => 'The selected source PAP code is invalid.',
            'description.required' => 'Please provide a short justification for this budget request.',
            'description.max' => 'The justification may not be greater than 1000 characters.',
            'attachment.required' => 'Please upload the supporting document.',
            'attachment.file' => 'The supporting basis must be a valid file.',
            'attachment.mimes' => 'The supporting basis must be a PDF, Word, JPG, or PNG file.',
            'attachment.max' => 'The supporting basis must not exceed 5MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (
                $this->input('request_type') === 'realignment' &&
                (int) $this->input('source_procurement_code_id') === (int) $this->route('id')
            ) {
                $validator->errors()->add(
                    'source_procurement_code_id',
                    'The source PAP code must be different from the target PAP code.'
                );
            }

            if (
                $this->input('request_type') === 'realignment' &&
                $this->filled('source_procurement_code_id') &&
                $this->filled('amount')
            ) {
                $sourceCode = ProcurementCode::find($this->input('source_procurement_code_id'));
                $remainingBudget = (float) ($sourceCode?->remaining_budget ?? $sourceCode?->allocated_budget ?? 0);

                if ((float) $this->input('amount') > $remainingBudget) {
                    $validator->errors()->add(
                        'amount',
                        'The realignment amount must not be greater than the source PAP code remaining balance.'
                    );
                }
            }
        });
    }
}
