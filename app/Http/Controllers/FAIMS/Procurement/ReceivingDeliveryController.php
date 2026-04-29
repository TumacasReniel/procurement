<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\FAIMS\Procurement\ReceivingDeliveryIndexRequest;
use App\Http\Requests\FAIMS\Procurement\ReceivingListRequest;
use App\Services\FAIMS\Procurement\IAReportClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ReceivingDeliveryController extends Controller
{
    use HandlesTransaction;

    public $receiving_deliveries;

    public function __construct(
        IAReportClass $receiving_deliveries
    ) {
        $this->receiving_deliveries = $receiving_deliveries;
    }

    public function index(ReceivingDeliveryIndexRequest $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->receiving_deliveries->lists($request);

            case 'selection':
                return $this->receiving_deliveries->selection($request);

            default:
                return inertia('Modules/FAIMS/Procurement/IAReport/Index', [
                    'mode' => 'receiving',
                ]);
        }
    }

    public function receivingList(ReceivingListRequest $request)
    {
        if ($request->option === 'lists') {
            return $this->receiving_deliveries->receivingRecords($request);
        }

        return inertia('Modules/FAIMS/Procurement/Receiving/List');
    }

    public function update($id, Request $request)
    {
        $result = $this->handleTransaction(function () use ($id, $request) {
            switch ($request->option) {
                case 'receive_delivery':
                    return $this->receiving_deliveries->receive($id, $request);

                default:
                    return [
                        'data' => null,
                        'message' => 'Invalid receiving option.',
                        'info' => 'No receiving delivery changes were applied.',
                        'status' => 'warning',
                    ];
            }
        });


        $isInertiaRequest = (bool) $request->header('X-Inertia');

        if (($request->expectsJson() || $request->ajax()) && !$isInertiaRequest) {
            return response()->json($result);
        }

        $response = back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'] ?? 'success',
        ]);

        if (!empty($result['errors'])) {
            $response->withErrors($result['errors']);
        }

        return $response;
    }
}
