<?php

namespace App\Http\Requests\Procurement;

use App\Models\Procurement;
use App\Models\ProcurementApp;
use App\Models\ProcurementCode;
use App\Models\ProcurementItem;
use App\Models\ProcurementPpmpItem;
use App\Models\ListStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->input('option') === 'cancel') {
            return [
                'option' => ['required', 'in:cancel'],
                'code' => ['nullable', 'string'],
            ];
        }

        $requiresProcurementCodes = in_array($this->input('option'), ['review', 'approve'], true);

        return [
            'procurement_code_ids' => [$requiresProcurementCodes ? 'required' : 'nullable', 'array', $requiresProcurementCodes ? 'min:1' : 'min:0'],
            'procurement_code_ids.*' => ['integer', 'distinct', 'exists:procurement_codes,id'],
            'procurement_app_id' => ['nullable', 'integer', 'exists:procurement_apps,id'],
            'unit_id' => ['nullable', 'integer'],
            'items' => ['nullable', 'array'],
            'items.*.ppmp_item_id' => ['nullable', 'integer', 'exists:procurement_ppmp_items,id'],
            'items.*.total_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'procurement_code_ids.required' => 'Select at least one PAP code before adding procurement items.',
            'procurement_code_ids.min' => 'Select at least one PAP code before adding procurement items.',
            'procurement_code_ids.*.exists' => 'One or more selected PAP codes are no longer available.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Gate: PR creation requires an Approved APP.
            // Only enforced on new PR submissions (POST) — updates/approvals are exempt.
            if ($this->isMethod('post') && $this->filled('procurement_app_id')) {
                $appId = (int) $this->input('procurement_app_id');
                $approvedStatusId = ListStatus::getID('Approved', 'Procurement');

                $isApproved = $approvedStatusId && ProcurementApp::query()
                    ->where('id', $appId)
                    ->where('status_id', $approvedStatusId)
                    ->exists();

                if (! $isApproved) {
                    $validator->errors()->add(
                        'procurement_app_id',
                        'Purchase Requests can only be created against an Approved/For Implementation APP. Please wait for the APP to be approved before submitting a PR.'
                    );
                }
            }

            $procurementCodeIds = collect($this->input('procurement_code_ids', []))
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($procurementCodeIds->isEmpty()) {
                return;
            }

            $submittedItems = collect($this->input('items', []));
            if ($this->isMethod('post') && $submittedItems->contains(fn ($item) => blank(data_get($item, 'ppmp_item_id')))) {
                $validator->errors()->add(
                    'items',
                    'Select each PR item from the items assigned to the selected procurement code.'
                );

                return;
            }

            $isCreateByCategory = $this->isCreateByCategoryRequest();

            if (!$isCreateByCategory && $this->filled('unit_id')) {
                $invalidEndUserCodes = ProcurementCode::query()
                    ->with('end_users.end_user')
                    ->whereIn('id', $procurementCodeIds)
                    ->whereDoesntHave('end_users', function ($query) {
                        $query->where('end_user_id', (int) $this->unit_id);
                    })
                    ->get()
                    ->map(function ($code) {
                        $assignedUnits = $code->end_users
                            ->map(fn ($endUser) => $endUser->end_user?->name ?: $endUser->end_user?->short)
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode(', ');

                        return trim($code->code . ($assignedUnits ? ' - change unit to ' . $assignedUnits : ''));
                    })
                    ->filter()
                    ->values();

                if ($invalidEndUserCodes->isNotEmpty()) {
                    $validator->errors()->add(
                        'procurement_code_ids',
                        'Selected PAP code(s) are not assigned to the selected unit. ' . $invalidEndUserCodes->implode('; ') . '.'
                    );
                }
            }

            $ppmpItemIds = $submittedItems
                ->pluck('ppmp_item_id')
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($ppmpItemIds->isNotEmpty()) {
                $validPPMPItemIds = ProcurementPpmpItem::query()
                    ->whereIn('id', $ppmpItemIds)
                    ->whereHas('ppmp', function ($query) use ($procurementCodeIds, $isCreateByCategory) {
                        $query
                            ->when(!$isCreateByCategory && $this->filled('unit_id'), fn ($unitQuery) => $unitQuery->where('unit_id', (int) $this->unit_id))
                            ->where(function ($ppmpQuery) use ($procurementCodeIds) {
                                $ppmpQuery
                                    ->whereHas('codes', function ($codeQuery) use ($procurementCodeIds) {
                                        $codeQuery->whereIn('procurement_code_id', $procurementCodeIds);
                                    })
                                    ->orDoesntHave('codes');
                            });
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                $invalidPPMPItemIds = $ppmpItemIds->diff($validPPMPItemIds);

                if ($invalidPPMPItemIds->isNotEmpty()) {
                    $validator->errors()->add(
                        'items',
                        'One or more selected items are not part of the selected unit PPMP/PAP code.'
                    );
                }
            }

            $requestedAmount = $submittedItems
                ->sum(fn ($item) => (float) data_get($item, 'total_cost', 0));

            if ($requestedAmount <= 0) {
                return;
            }

            $availableAmount = ProcurementCode::query()
                ->whereIn('id', $procurementCodeIds)
                ->get(['remaining_budget', 'allocated_budget'])
                ->sum(function ($code) {
                    return (float) ($code->remaining_budget ?? $code->allocated_budget ?? 0);
                });

            if (($availableAmount + 0.009) >= $requestedAmount) {
                return;
            }

            $validator->errors()->add(
                'procurement_code_ids',
                sprintf(
                    'The selected PAP codes only have PHP %s remaining, which is not enough for the request total of PHP %s.',
                    number_format($availableAmount, 2),
                    number_format($requestedAmount, 2)
                )
            );
        });
    }

    protected function isCreateByCategoryRequest(): bool
    {
        if ($this->input('option') === 'create_by_category') {
            return true;
        }

        $procurementId = (int) ($this->input('id') ?: $this->route('procurement') ?: 0);

        if (!$procurementId) {
            return false;
        }

        $procurement = Procurement::query()->find($procurementId, ['id', 'unit_id']);

        if (!$procurement || !$procurement->unit_id) {
            return false;
        }

        return ProcurementItem::query()
            ->where('procurement_id', $procurement->id)
            ->whereHas('ppmp_item.ppmp', function ($query) use ($procurement) {
                $query->whereNotNull('unit_id')
                    ->where('unit_id', '!=', $procurement->unit_id);
            })
            ->exists();
    }
}
