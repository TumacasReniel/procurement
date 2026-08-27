<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Inventory\Concerns\AuthorizesInventoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRisRequest extends FormRequest
{
    use AuthorizesInventoryAccess;

    public function rules(): array
    {
        $risId = $this->route('inventory_ri') ?? $this->input('id');
        $risId = is_object($risId) ? $risId->id : $risId;

        return [
            'ris_no'                => ['nullable', 'string', 'max:50', Rule::unique('inventory_ris', 'ris_no')->ignore($risId)],
            'fund_cluster'          => ['nullable', 'string', 'max:100'],
            'division'              => ['nullable', 'string', 'max:150'],
            'responsibility_center' => ['nullable', 'string', 'max:150'],
            'purpose'               => ['nullable', 'string'],
            'ris_date'              => ['required', 'date'],
            'requested_by_id'       => ['nullable', 'exists:users,id'],
            'approved_by_id'        => ['nullable', 'exists:users,id'],
            'issued_by_id'          => ['nullable', 'exists:users,id'],
            'received_by_id'        => ['nullable', 'exists:users,id'],
            'status_id'             => [$this->isMethod('PUT') ? 'required' : 'nullable', 'exists:list_statuses,id'],
            'items'                 => ['nullable', 'array'],
            'items.*.item_id'           => ['required', 'exists:inventory_items,id'],
            'items.*.unit_of_issue'     => ['nullable', 'string', 'max:50'],
            'items.*.quantity_requested'=> ['required', 'numeric', 'min:0'],
            'items.*.quantity_issued'   => ['nullable', 'numeric', 'min:0', 'lte:items.*.quantity_requested'],
            'items.*.remarks'           => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ris_date.required'             => 'Please enter the RIS date.',
            'status_id.required'            => 'Please select a status.',
            'items.*.item_id.required'      => 'Each line must have an item.',
            'items.*.quantity_requested.required' => 'Each line must have a requested quantity.',
            'items.*.quantity_issued.lte' => 'Issued quantity cannot exceed the requested quantity.',
        ];
    }
}
