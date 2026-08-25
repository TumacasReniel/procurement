<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementPPMPListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'exists:list_statuses,id'],
            'unit' => ['nullable', 'integer', 'exists:list_units,id'],
            'unit_id' => ['nullable', 'integer', 'exists:list_units,id'],
            'plan_type' => ['nullable', 'in:PPMP,ppmp,APP,annual,SPP,supplemental'],
            'sort' => ['nullable', 'in:latest,oldest,pr_asc,pr_desc'],
            'count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.(date('Y') + 10)],
            'option' => ['nullable', 'string', 'max:50'],
        ];
    }
}
