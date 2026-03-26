<?php

namespace App\Services\FAIMS\Finance;

use App\Http\Resources\FAIMS\Finance\FinanceRequestResource;
use App\Models\FinanceRequest;

class ViewClass
{
    /**
     * Get paginated list of finance requests
     */
    public function lists($request)
    {
        $query = FinanceRequest::with(['status', 'division', 'request_type', 'created_by.profile'])
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('payee', 'LIKE', "%{$keyword}%")
                        ->orWhere('requested_by', 'LIKE', "%{$keyword}%")
                        ->orWhere('amount', 'LIKE', "%{$keyword}%");
                })
                ->when($request->status, function ($query, $status) {
                    $value = $status;
                    if (is_array($status)) {
                        $value = $status['value'] ?? $status['id'] ?? null;
                    } elseif (is_object($status)) {
                        $value = $status->value ?? $status->id ?? null;
                    }
                    if ($value) {
                        $query->where('status_id', $value);
                    }
                })
                ->orderBy('created_at', 'DESC');

        $data = FinanceRequestResource::collection($query->paginate($request->count ?? 10));

        return $data;
    }
}


