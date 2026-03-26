<?php

namespace App\Services\FAIMS\Finance;

use App\Models\FinanceRequest;
use App\Models\ListData;
use App\Models\ListStatus;
use App\Models\Request as BaseRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceClass
{
    private function normalizeDate($value)
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function extractSelectValue($value)
    {
        if (is_array($value)) {
            return $value['value'] ?? $value['id'] ?? null;
        }

        if (is_object($value)) {
            return $value->value ?? $value->id ?? null;
        }

        return $value;
    }

    private function resolveCreditor($creditor)
    {
        if (!$creditor) {
            return [null, null, null];
        }

        $label = null;
        $type = null;
        $value = null;
        $model = null;

        if (is_array($creditor)) {
            $label = $creditor['label'] ?? $creditor['name'] ?? null;
            $type = $creditor['type'] ?? null;
            $value = $creditor['value'] ?? $creditor['id'] ?? null;
            $model = $creditor['model'] ?? null;
        } elseif (is_object($creditor)) {
            $label = $creditor->label ?? $creditor->name ?? null;
            $type = $creditor->type ?? null;
            $value = $creditor->value ?? $creditor->id ?? null;
            $model = $creditor->model ?? null;
        } else {
            $value = $creditor;
        }

        $creditorId = null;
        $creditorType = null;

        if (is_string($value) && str_contains($value, '_')) {
            [$typePart, $idPart] = explode('_', $value, 2);
            $type = $type ?? $typePart;
            if (is_numeric($idPart)) {
                $creditorId = (int) $idPart;
            }
        } elseif (is_numeric($value)) {
            $creditorId = (int) $value;
        }

        if ($model && class_exists($model)) {
            $creditorType = $model;
        } else {
            switch ($type) {
                case 'supplier':
                    $creditorType = \App\Models\Supplier::class;
                    break;
                case 'user':
                    $creditorType = \App\Models\User::class;
                    break;
                case 'finance_creditor':
                    $creditorType = \App\Models\FinanceCreditor::class;
                    break;
            }
        }

        return [$creditorId, $creditorType, $label];
    }

    private function generateRequestCode(): string
    {
        return \DB::transaction(function () {
            $prefix = 'REQUEST-' . now()->format('mY') . '-FR-';

            $latest = BaseRequest::lockForUpdate()
                ->where('code', 'like', $prefix . '%')
                ->orderByDesc('id')
                ->first();

            $count = $latest
                ? (int) substr($latest->code, -4) + 1
                : 1;

            return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    private function getRequestTypeId(): int
    {
        return ListData::getID('Finance Request')
            ?? ListData::getID('Finance')
            ?? ListData::getID('Request')
            ?? ListData::getID('n/a')
            ?? 1;
    }

    public function save(Request $request)
    {
        [$creditorId, $creditorType, $payeeLabel] = $this->resolveCreditor($request->creditor_id ?? $request->payee);

        $baseRequest = BaseRequest::create([
            'code' => $this->generateRequestCode(),
            'type_id' => $this->getRequestTypeId(),
            'user_id' => auth()->id(),
        ]);

        $financeRequest = FinanceRequest::create([
            'request_id' => $baseRequest->id,
            'date' => $this->normalizeDate($request->request_date),
            'request_type_id' => $this->extractSelectValue($request->request_type_id ?? $request->request_type),
            'division_id' => $this->extractSelectValue($request->division_id ?? $request->division),
            'fund_cluster_id' => $this->extractSelectValue($request->fund_source_id ?? $request->fund_source),
            'project_type_id' => $this->extractSelectValue($request->project_type_id),
            'project_id' => $this->extractSelectValue($request->project_id ?? $request->project),
            'creditor_id' => $creditorId,
            'creditor_type' => $creditorType,
            'requested_by' => auth()->user()->profile->full_name ?? auth()->user()->name,
            'amount' => $request->amount,
            'particulars' => $request->particulars,
            'created_by_id' => auth()->id(),
            'status' => ListStatus::getID('Pending', 'Finance Request'),
        ]);

        return [
            'data' => $financeRequest,
            'message' => 'Finance request created successfully.',
            'info' => null,
            'status' => true,
        ];
    }

    public function update(Request $request, $id)
    {
        $financeRequest = FinanceRequest::findOrFail($id);
        [$creditorId, $creditorType, $payeeLabel] = $this->resolveCreditor($request->creditor_id ?? $request->payee);

        $financeRequest->update([
            'date' => $this->normalizeDate($request->request_date),
            'request_type_id' => $this->extractSelectValue($request->request_type_id ?? $request->request_type),
            'division_id' => $this->extractSelectValue($request->division_id ?? $request->division),
            'fund_cluster_id' => $this->extractSelectValue($request->fund_source_id ?? $request->fund_source),
            'project_type_id' => $this->extractSelectValue($request->project_type_id),
            'project_id' => $this->extractSelectValue($request->project_id ?? $request->project),
            'creditor_id' => $creditorId,
            'creditor_type' => $creditorType,
            'payee' => $payeeLabel,
            'amount' => $request->amount,
            'particulars' => $request->particulars,
        ]);

        return [
            'data' => $financeRequest->fresh(),
            'message' => 'Finance request updated successfully.',
            'info' => null,
            'status' => true,
        ];
    }
}
