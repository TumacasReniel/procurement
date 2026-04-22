<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    protected array $requiredDocumentTypes = [
        'Business Permit',
        'PhilGEPS Registration',
    ];

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
            'tin' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'conformes' => ['nullable', 'array'],
            'conformes.*.name' => ['nullable', 'string', 'max:255'],
            'conformes.*.position' => ['nullable', 'string', 'max:255'],
            'conformes.*.contact_no' => ['nullable', 'string', 'max:50'],
            'attachment_rows' => ['nullable', 'array'],
            'attachment_rows.*.id' => ['nullable', 'integer', 'exists:supplier_attachments,id'],
            'attachment_rows.*.document_type' => ['nullable', 'string', 'max:255'],
            'attachment_rows.*.code' => ['nullable', 'string', 'max:255'],
            'attachment_rows.*.file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rows = $this->input('attachment_rows', []);

            foreach ($rows as $index => $row) {
                $documentType = trim((string) data_get($row, 'document_type', ''));
                $hasExistingAttachment = filled(data_get($row, 'id'));
                $hasUploadedFile = $this->hasFile("attachment_rows.$index.file");
                $hasCode = filled(data_get($row, 'code'));
                $hasAnyValue = $documentType !== '' || $hasExistingAttachment || $hasUploadedFile || $hasCode;

                if (!$hasAnyValue) {
                    continue;
                }

                if ($documentType === '') {
                    $validator->errors()->add(
                        "attachment_rows.$index.document_type",
                        'Please enter the document name.'
                    );
                }

                if (!$hasExistingAttachment && !$hasUploadedFile) {
                    $validator->errors()->add(
                        "attachment_rows.$index.file",
                        'Please upload the attachment file.'
                    );
                }
            }

            foreach ($this->requiredDocumentTypes as $requiredDocumentType) {
                $matchingIndex = collect($rows)->keys()->first(function ($index) use ($rows, $requiredDocumentType) {
                    $documentType = data_get($rows, "$index.document_type");

                    return $this->normalizeDocumentType($documentType) === $this->normalizeDocumentType($requiredDocumentType);
                });

                if ($matchingIndex === null) {
                    $validator->errors()->add(
                        'attachment_rows',
                        "{$requiredDocumentType} is required."
                    );
                    continue;
                }

                $hasExistingAttachment = filled(data_get($rows, "$matchingIndex.id"));
                $hasUploadedFile = $this->hasFile("attachment_rows.$matchingIndex.file");

                if (!$hasExistingAttachment && !$hasUploadedFile) {
                    $validator->errors()->add(
                        "attachment_rows.$matchingIndex.file",
                        "{$requiredDocumentType} is required."
                    );
                }
            }
        });
    }

    public function messages()
    {
        return [
            'name.required' => 'This field is required',
            'name.unique' => 'Supplier name already exists',
            'code.unique' => 'Supplier code already exists',
            'attachment_rows.*.file.max' => 'Each attachment must not exceed 5MB.',
            'attachment_rows.*.file.mimes' => 'Attachments must be PDF, Word, JPG, or PNG files only.',
        ];
    }

    protected function normalizeDocumentType($value): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim((string) $value)));
    }
}
