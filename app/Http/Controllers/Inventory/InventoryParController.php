<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryParRequest;
use App\Http\Resources\Inventory\InventoryParResource;
use App\Models\InventoryPar;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryParController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        return $this->stockService->parList($request);
    }

    public function show(InventoryPar $inventory_par)
    {
        return new InventoryParResource($inventory_par->load([
            'items.item', 'receivedBy.profile', 'issuedBy.profile', 'approvedBy.profile', 'status',
        ]));
    }

    public function store(InventoryParRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->savePar($request);
        });

        return $this->inventoryResultResponse($request, $result, 'par');
    }

    public function update(InventoryParRequest $request, InventoryPar $inventory_par)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_par) {
            return $this->stockService->updatePar($request, $inventory_par);
        });

        return $this->inventoryResultResponse($request, $result, 'par');
    }

    public function destroy(Request $request, InventoryPar $inventory_par)
    {
        $result = $this->handleTransaction(function () use ($inventory_par) {
            return $this->stockService->deletePar($inventory_par);
        });

        return $this->inventoryResultResponse($request, $result, 'par');
    }
}
