<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryStockRequest;
use App\Models\InventoryStock;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryStockController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(public InventoryStockClass $inventory)
    {
    }

    public function index(Request $request)
    {
        if ($this->shouldReturnJson($request)) {
            return $this->inventory->stocks($request);
        }

        return Inertia::render('Modules/Inventory/Index', array_merge(
            ['initialTab' => $this->resolveTab((string) $request->query('tab', 'stocks'))],
            $this->inventory->indexData($request)
        ));
    }

    public function store(InventoryStockRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->inventory->saveStock($request);
        });

        return $this->inventoryResultResponse($request, $result, 'stocks');
    }

    public function update(InventoryStockRequest $request, InventoryStock $inventory_stock)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_stock) {
            return $this->inventory->updateStock($request, $inventory_stock);
        });

        return $this->inventoryResultResponse($request, $result, 'stocks');
    }

    public function destroy(Request $request, InventoryStock $inventory_stock)
    {
        $result = $this->handleTransaction(function () use ($inventory_stock) {
            return $this->inventory->deleteStock($inventory_stock);
        });

        return $this->inventoryResultResponse($request, $result, 'stocks');
    }

    protected function resolveTab(string $tab): string
    {
        return in_array($tab, ['stocks', 'items', 'receivings', 'withdrawals'], true)
            ? $tab
            : 'stocks';
    }
}
