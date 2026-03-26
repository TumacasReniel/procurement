<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryStockRequest;
use App\Models\InventoryStock;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryStockController extends Controller
{
    use HandlesTransaction;

    public function __construct(public InventoryStockClass $inventoryStock)
    {
    }

    public function index(Request $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->inventoryStock->lists($request);

            default:
                return Inertia::render('Modules/InventoryStocks/Index', $this->inventoryStock->indexData());
        }
    }

    public function store(InventoryStockRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->inventoryStock->save($request);
        });

        return response()->json([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function update(InventoryStockRequest $request, InventoryStock $inventory_stock)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_stock) {
            return $this->inventoryStock->update($request, $inventory_stock);
        });

        return response()->json([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function destroy(InventoryStock $inventory_stock)
    {
        $result = $this->handleTransaction(function () use ($inventory_stock) {
            return $this->inventoryStock->delete($inventory_stock);
        });

        return response()->json([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
