<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryReceivingRequest;
use App\Models\InventoryReceiving;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryReceivingController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(public InventoryStockClass $inventory)
    {
    }

    public function index(Request $request)
    {
        if (!$this->shouldReturnJson($request)) {
            return redirect('/inventory-stocks?tab=receivings');
        }

        return $this->inventory->receivings($request);
    }

    public function store(InventoryReceivingRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->inventory->saveReceiving($request);
        });

        return $this->inventoryResultResponse($request, $result, 'receivings');
    }

    public function update(InventoryReceivingRequest $request, InventoryReceiving $inventory_receiving)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_receiving) {
            return $this->inventory->updateReceiving($request, $inventory_receiving);
        });

        return $this->inventoryResultResponse($request, $result, 'receivings');
    }

    public function destroy(Request $request, InventoryReceiving $inventory_receiving)
    {
        $result = $this->handleTransaction(function () use ($inventory_receiving) {
            return $this->inventory->deleteReceiving($inventory_receiving);
        });

        return $this->inventoryResultResponse($request, $result, 'receivings');
    }
}
