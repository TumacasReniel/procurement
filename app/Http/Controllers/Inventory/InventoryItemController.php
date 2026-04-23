<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(public InventoryStockClass $inventory)
    {
    }

    public function index(Request $request)
    {
        if (!$this->shouldReturnJson($request)) {
            return redirect('/inventory-stocks?tab=items');
        }

        return $this->inventory->items($request);
    }

    public function store(InventoryItemRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->inventory->saveItem($request);
        });

        return $this->inventoryResultResponse($request, $result, 'items');
    }

    public function update(InventoryItemRequest $request, InventoryItem $inventory_item)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_item) {
            return $this->inventory->updateItem($request, $inventory_item);
        });

        return $this->inventoryResultResponse($request, $result, 'items');
    }

    public function destroy(Request $request, InventoryItem $inventory_item)
    {
        $result = $this->handleTransaction(function () use ($inventory_item) {
            return $this->inventory->deleteItem($inventory_item);
        });

        return $this->inventoryResultResponse($request, $result, 'items');
    }
}
