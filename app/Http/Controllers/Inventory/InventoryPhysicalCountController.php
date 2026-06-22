<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryPhysicalCountRequest;
use App\Http\Resources\Inventory\InventoryPhysicalCountResource;
use App\Models\InventoryPhysicalCount;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryPhysicalCountController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        return $this->stockService->physicalCountList($request);
    }

    public function show(InventoryPhysicalCount $inventory_physical_count)
    {
        return new InventoryPhysicalCountResource($inventory_physical_count->load([
            'items.item:id,code,name',
            'countedBy.profile',
            'verifiedBy.profile',
            'approvedBy.profile',
            'status',
        ]));
    }

    public function store(InventoryPhysicalCountRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->savePhysicalCount($request);
        });

        return $this->inventoryResultResponse($request, $result, 'physical-count');
    }

    public function update(InventoryPhysicalCountRequest $request, InventoryPhysicalCount $inventory_physical_count)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_physical_count) {
            return $this->stockService->updatePhysicalCount($request, $inventory_physical_count);
        });

        return $this->inventoryResultResponse($request, $result, 'physical-count');
    }

    public function destroy(Request $request, InventoryPhysicalCount $inventory_physical_count)
    {
        $result = $this->handleTransaction(function () use ($inventory_physical_count) {
            return $this->stockService->deletePhysicalCount($inventory_physical_count);
        });

        return $this->inventoryResultResponse($request, $result, 'physical-count');
    }
}
