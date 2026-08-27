<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title_id' => ['required', 'integer', 'exists:inventory_report_titles,id'],
            'category_id' => ['required', 'integer', 'exists:list_dropdowns,id'],
            'period_type' => ['required', 'in:daily,weekly,monthly,quarterly,yearly,custom'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'period_quarter' => ['nullable', 'integer', 'min:1', 'max:4'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'period_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
