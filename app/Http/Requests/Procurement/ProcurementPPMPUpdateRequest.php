<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementPPMPUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'option' => ['required', 'in:update_status,approve_to_app,add_item,update_item,delete_item'],
            'plan_type' => ['nullable', 'in:PPMP,ppmp,APP,SPP,annual,supplemental'],
        ];

        if ($this->option === 'add_item') {
            $rules['items'] = ['required', 'array', 'min:1'];
            $rules['items.*.item_name'] = ['required', 'string', 'max:255'];
            $rules['items.*.item_description'] = ['required', 'string'];
            $rules['items.*.item_quantity'] = ['required', 'numeric', 'min:0.0001'];
            $rules['items.*.item_unit_type_id'] = ['required', 'integer', 'exists:unit_types,id'];
            $rules['items.*.item_unit_cost'] = ['required', 'numeric', 'min:0'];

            $rules['item_name'] = ['nullable', 'string', 'max:255'];
            $rules['item_description'] = ['nullable', 'string'];
            $rules['item_quantity'] = ['nullable', 'numeric', 'min:0.0001'];
            $rules['item_unit_type_id'] = ['nullable', 'integer', 'exists:unit_types,id'];
            $rules['item_unit_cost'] = ['nullable', 'numeric', 'min:0'];
            $rules['general_description_objective'] = ['required', 'string'];
            $rules['project_type'] = ['required', 'string', 'max:255'];
            $rules['item_category_id'] = ['required', 'integer', 'exists:list_dropdowns,id'];
            $rules['recommended_mode_of_procurement'] = ['required', 'string', 'max:255'];
            $rules['pre_procurement_conference'] = ['required', 'string', 'max:255'];
            $rules['start_of_procurement_activity'] = ['required', 'date'];
            $rules['end_of_procurement_activity'] = ['required', 'date'];
            $rules['expected_delivery_date'] = ['required', 'date'];
            $rules['attached_supporting_documents'] = ['required', 'string', 'max:255'];
            $rules['supporting_document_file'] = ['required', 'file', 'mimes:pdf', 'max:10240'];
            $rules['remarks'] = ['required', 'string'];
        }

        if ($this->option === 'update_item') {
            $rules['item_id'] = ['required', 'integer', 'exists:procurement_ppmp_items,id'];
            $rules['items'] = ['required', 'array', 'min:1'];
            $rules['items.*.id'] = ['required', 'integer', 'exists:procurement_ppmp_items,id'];
            $rules['items.*.item_name'] = ['required', 'string', 'max:255'];
            $rules['items.*.item_description'] = ['required', 'string'];
            $rules['items.*.item_quantity'] = ['required', 'numeric', 'min:0.0001'];
            $rules['items.*.item_unit_type_id'] = ['required', 'integer', 'exists:unit_types,id'];
            $rules['items.*.item_unit_cost'] = ['required', 'numeric', 'min:0'];
            $rules['item_name'] = ['nullable', 'string', 'max:255'];
            $rules['item_description'] = ['nullable', 'string'];
            $rules['item_quantity'] = ['nullable', 'numeric', 'min:0.0001'];
            $rules['item_unit_type_id'] = ['nullable', 'integer', 'exists:unit_types,id'];
            $rules['item_unit_cost'] = ['nullable', 'numeric', 'min:0'];
            $rules['general_description_objective'] = ['required', 'string'];
            $rules['project_type'] = ['required', 'string', 'max:255'];
            $rules['item_category_id'] = ['required', 'integer', 'exists:list_dropdowns,id'];
            $rules['recommended_mode_of_procurement'] = ['required', 'string', 'max:255'];
            $rules['pre_procurement_conference'] = ['required', 'string', 'max:255'];
            $rules['start_of_procurement_activity'] = ['required', 'date'];
            $rules['end_of_procurement_activity'] = ['required', 'date'];
            $rules['expected_delivery_date'] = ['required', 'date'];
            $rules['attached_supporting_documents'] = ['required', 'string', 'max:255'];
            $rules['supporting_document_file'] = ['nullable', 'file', 'mimes:pdf', 'max:10240'];
            $rules['remarks'] = ['required', 'string'];
        }

        if ($this->option === 'delete_item') {
            $rules['item_id'] = ['required', 'integer', 'exists:procurement_ppmp_items,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please add at least one item to the table.',
            'items.min' => 'Please add at least one item to the table.',
            'items.*.item_name.required' => 'Each item must have an item name.',
            'items.*.item_description.required' => 'Each item must have a description.',
            'items.*.item_quantity.required' => 'Each item must have a quantity.',
            'items.*.item_unit_type_id.required' => 'Each item must have a unit type.',
            'items.*.item_unit_type_id.exists' => 'The selected unit type is invalid.',
            'items.*.item_unit_cost.required' => 'Each item must have a unit cost.',
            'general_description_objective.required' => 'Please enter the general description and objective.',
            'project_type.required' => 'Please select the type of project to be procured.',
            'item_category_id.required' => 'Please select the item category.',
            'item_category_id.exists' => 'The selected item category is invalid.',
            'recommended_mode_of_procurement.required' => 'Please select the recommended mode of procurement.',
            'pre_procurement_conference.required' => 'Please select if a pre-procurement conference is applicable.',
            'start_of_procurement_activity.required' => 'Please select the start of procurement activity.',
            'end_of_procurement_activity.required' => 'Please select the end of procurement activity.',
            'expected_delivery_date.required' => 'Please select the expected delivery date.',
            'attached_supporting_documents.required' => 'Please select or enter the supporting document type.',
            'supporting_document_file.required' => 'Please attach the supporting document file.',
            'supporting_document_file.mimes' => 'The supporting document must be a PDF file.',
            'remarks.required' => 'Please enter remarks.',
            'item_id.required' => 'Please select an item to update.',
            'item_name.required' => 'Please enter the item name.',
            'item_description.required' => 'Please enter the item description.',
            'item_quantity.required' => 'Please enter the item quantity.',
            'item_unit_type_id.required' => 'Please select the unit type.',
            'item_unit_cost.required' => 'Please enter the unit cost.',
        ];
    }

}
