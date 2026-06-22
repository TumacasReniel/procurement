<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryIcsRequest;
use App\Http\Resources\Inventory\InventoryIcsResource;
use App\Models\InventoryIcs;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryIcsController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        return $this->stockService->icsList($request);
    }

    public function show(InventoryIcs $inventory_ic)
    {
        return new InventoryIcsResource($inventory_ic->load([
            'items.item', 'issuedTo.profile', 'issuedBy.profile', 'approvedBy.profile', 'status',
        ]));
    }

    public function store(InventoryIcsRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->saveIcs($request);
        });

        return $this->inventoryResultResponse($request, $result, 'ics');
    }

    public function update(InventoryIcsRequest $request, InventoryIcs $inventory_ic)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_ic) {
            return $this->stockService->updateIcs($request, $inventory_ic);
        });

        return $this->inventoryResultResponse($request, $result, 'ics');
    }

    public function destroy(Request $request, InventoryIcs $inventory_ic)
    {
        $result = $this->handleTransaction(function () use ($inventory_ic) {
            return $this->stockService->deleteIcs($inventory_ic);
        });

        return $this->inventoryResultResponse($request, $result, 'ics');
    }
}
