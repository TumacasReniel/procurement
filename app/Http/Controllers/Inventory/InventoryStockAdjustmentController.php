<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryStockAdjustmentRequest;
use App\Models\InventoryStockAdjustment;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryStockAdjustmentController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        return $this->stockService->stockAdjustments($request);
    }

    public function store(InventoryStockAdjustmentRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->saveStockAdjustment($request);
        });

        return $this->inventoryResultResponse($request, $result, 'stocks');
    }

    public function update(InventoryStockAdjustmentRequest $request, InventoryStockAdjustment $inventory_stock_adjustment)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_stock_adjustment) {
            return $this->stockService->updateStockAdjustment($request, $inventory_stock_adjustment);
        });

        return $this->inventoryResultResponse($request, $result, 'stocks');
    }

    public function destroy(Request $request, InventoryStockAdjustment $inventory_stock_adjustment)
    {
        $result = $this->handleTransaction(function () use ($inventory_stock_adjustment) {
            return $this->stockService->deleteStockAdjustment($inventory_stock_adjustment);
        });

        return $this->inventoryResultResponse($request, $result, 'stocks');
    }
}
