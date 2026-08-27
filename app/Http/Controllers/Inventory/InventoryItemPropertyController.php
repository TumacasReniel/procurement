<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryItemPropertyRequest;
use App\Models\InventoryItemProperty;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryItemPropertyController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(public InventoryStockClass $inventory)
    {
    }

    public function index(Request $request)
    {
        if (!$this->shouldReturnJson($request)) {
            return redirect('/inventory-stocks?tab=properties');
        }

        return $this->inventory->itemProperties($request);
    }

    public function store(InventoryItemPropertyRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->inventory->saveItemProperty($request);
        });

        return $this->inventoryResultResponse($request, $result, 'properties');
    }

    public function update(InventoryItemPropertyRequest $request, InventoryItemProperty $inventory_item_property)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_item_property) {
            return $this->inventory->updateItemProperty($request, $inventory_item_property);
        });

        return $this->inventoryResultResponse($request, $result, 'properties');
    }

    public function destroy(Request $request, InventoryItemProperty $inventory_item_property)
    {
        $result = $this->handleTransaction(function () use ($inventory_item_property) {
            return $this->inventory->deleteItemProperty($inventory_item_property);
        });

        return $this->inventoryResultResponse($request, $result, 'properties');
    }
}
