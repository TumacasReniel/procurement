<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementPPMPUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'option' => ['required', 'in:submit_final,add_item'],
        ];

        if ($this->option === 'add_item') {
            $hasRows = is_array($this->items) && count($this->items) > 0;

            $rules['items'] = ['nullable', 'array'];
            $rules['items.*.item_name'] = ['required_with:items', 'string', 'max:255'];
            $rules['items.*.item_description'] = ['required_with:items', 'string'];
            $rules['items.*.item_quantity'] = ['required_with:items', 'numeric', 'min:0.0001'];
            $rules['items.*.item_unit_type_id'] = ['required_with:items', 'integer', 'exists:unit_types,id'];
            $rules['items.*.item_unit_cost'] = ['required_with:items', 'numeric', 'min:0'];

            $rules['item_name'] = [$hasRows ? 'nullable' : 'required', 'string', 'max:255'];
            $rules['item_description'] = [$hasRows ? 'nullable' : 'required', 'string'];
            $rules['item_quantity'] = [$hasRows ? 'nullable' : 'required', 'numeric', 'min:0.0001'];
            $rules['item_unit_type_id'] = [$hasRows ? 'nullable' : 'required', 'integer', 'exists:unit_types,id'];
            $rules['item_unit_cost'] = [$hasRows ? 'nullable' : 'required', 'numeric', 'min:0'];
            $rules['project_type'] = ['required', 'string', 'max:255'];
            $rules['recommended_mode_of_procurement'] = ['required', 'string', 'max:255'];
            $rules['end_of_procurement_activity'] = ['nullable', 'date'];
            $rules['expected_delivery_date'] = ['nullable', 'date'];
            $rules['attached_supporting_documents'] = ['nullable', 'string', 'max:255'];
            $rules['supporting_document_file'] = ['nullable', 'file', 'max:10240'];
            $rules['remarks'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
