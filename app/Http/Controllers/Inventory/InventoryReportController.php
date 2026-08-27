<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryReportRequest;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        return $this->stockService->reports($request);
    }

    public function show(int $id)
    {
        return $this->stockService->showReport($id);
    }

    public function store(InventoryReportRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->saveReport($request);
        });

        return $this->inventoryResultResponse($request, $result, 'report');
    }

    public function update(InventoryReportRequest $request, int $id)
    {
        $result = $this->handleTransaction(function () use ($request, $id) {
            return $this->stockService->updateReport($request, $id);
        });

        return $this->inventoryResultResponse($request, $result, 'report');
    }

    public function destroy(Request $request, int $id)
    {
        $result = $this->handleTransaction(function () use ($id) {
            return $this->stockService->deleteReport($id);
        });

        return $this->inventoryResultResponse($request, $result, 'report');
    }
}
