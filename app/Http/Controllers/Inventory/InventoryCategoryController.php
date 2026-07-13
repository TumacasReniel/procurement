<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryCategoryRequest;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index()
    {
        return $this->stockService->categories();
    }

    public function store(InventoryCategoryRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->saveCategory($request);
        });

        return $this->inventoryResultResponse($request, $result, 'categories');
    }

    public function update(InventoryCategoryRequest $request, int $id)
    {
        $result = $this->handleTransaction(function () use ($request, $id) {
            return $this->stockService->updateCategory($request, $id);
        });

        return $this->inventoryResultResponse($request, $result, 'categories');
    }

    public function destroy(Request $request, int $id)
    {
        $result = $this->handleTransaction(function () use ($id) {
            return $this->stockService->deleteCategory($id);
        });

        return $this->inventoryResultResponse($request, $result, 'categories');
    }
}
