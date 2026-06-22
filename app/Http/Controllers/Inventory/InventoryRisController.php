<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryRisRequest;
use App\Http\Resources\Inventory\InventoryRisResource;
use App\Models\InventoryRis;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryRisController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        return $this->stockService->risList($request);
    }

    public function show(InventoryRis $inventory_ri)
    {
        return new InventoryRisResource($inventory_ri->load([
            'items.item',
            'requestedBy.profile',
            'approvedBy.profile',
            'issuedBy.profile',
            'receivedBy.profile',
            'status',
        ]));
    }

    public function store(InventoryRisRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->saveRis($request);
        });

        return $this->inventoryResultResponse($request, $result, 'ris');
    }

    public function update(InventoryRisRequest $request, InventoryRis $inventory_ri)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_ri) {
            return $this->stockService->updateRis($request, $inventory_ri);
        });

        return $this->inventoryResultResponse($request, $result, 'ris');
    }

    public function destroy(Request $request, InventoryRis $inventory_ri)
    {
        $result = $this->handleTransaction(function () use ($inventory_ri) {
            return $this->stockService->deleteRis($inventory_ri);
        });

        return $this->inventoryResultResponse($request, $result, 'ris');
    }

    public function void(Request $request, InventoryRis $inventory_ri)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrator', 'Supply Officer']), 403);

        $result = $this->handleTransaction(function () use ($inventory_ri) {
            return $this->stockService->voidRis($inventory_ri);
        });

        return $this->inventoryResultResponse($request, $result, 'ris');
    }
}
