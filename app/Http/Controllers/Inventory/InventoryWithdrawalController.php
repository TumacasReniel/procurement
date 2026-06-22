<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryWithdrawalRequest;
use App\Models\InventoryWithdrawal;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryWithdrawalController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(private InventoryStockClass $stockService) {}

    public function index(Request $request)
    {
        if (! $this->shouldReturnJson($request)) {
            return redirect('/inventory-stocks?tab=withdrawals');
        }

        return $this->stockService->withdrawals($request);
    }

    public function store(InventoryWithdrawalRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->stockService->saveWithdrawal($request);
        });

        return $this->inventoryResultResponse($request, $result, 'withdrawals');
    }

    public function update(InventoryWithdrawalRequest $request, InventoryWithdrawal $inventory_withdrawal)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_withdrawal) {
            return $this->stockService->updateWithdrawal($request, $inventory_withdrawal);
        });

        return $this->inventoryResultResponse($request, $result, 'withdrawals');
    }

    public function destroy(Request $request, InventoryWithdrawal $inventory_withdrawal)
    {
        $result = $this->handleTransaction(function () use ($inventory_withdrawal) {
            return $this->stockService->deleteWithdrawal($inventory_withdrawal);
        });

        return $this->inventoryResultResponse($request, $result, 'withdrawals');
    }

    public function void(Request $request, InventoryWithdrawal $inventory_withdrawal)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrator', 'Supply Officer']), 403);

        $result = $this->handleTransaction(function () use ($inventory_withdrawal) {
            return $this->stockService->voidWithdrawal($inventory_withdrawal);
        });

        return $this->inventoryResultResponse($request, $result, 'withdrawals');
    }

    public function partialIssue(Request $request, InventoryWithdrawal $inventory_withdrawal)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrator', 'Supply Officer', 'Supply Staff']), 403);

        $result = $this->handleTransaction(function () use ($request, $inventory_withdrawal) {
            return $this->stockService->partialIssueWithdrawal($request, $inventory_withdrawal);
        });

        return $this->inventoryResultResponse($request, $result, 'withdrawals');
    }
}
