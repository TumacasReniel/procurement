<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryWithdrawalRequest;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
use App\Models\InventoryWithdrawal;
use Illuminate\Http\Request;

class InventoryWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $withdrawals = InventoryWithdrawal::with(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])
            ->orderByDesc('id')
            ->paginate((int) $request->input('count', 10));

        return InventoryWithdrawalResource::collection($withdrawals);
    }

    public function store(InventoryWithdrawalRequest $request)
    {
        $withdrawal = InventoryWithdrawal::create($request->validated());

        return response()->json([
            'data' => new InventoryWithdrawalResource($withdrawal->load(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'Inventory withdrawal created successfully.',
        ]);
    }

    public function update(InventoryWithdrawalRequest $request, InventoryWithdrawal $inventory_withdrawal)
    {
        $inventory_withdrawal->update($request->validated());

        return response()->json([
            'data' => new InventoryWithdrawalResource($inventory_withdrawal->load(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'Inventory withdrawal updated successfully.',
        ]);
    }

    public function destroy(InventoryWithdrawal $inventory_withdrawal)
    {
        $inventory_withdrawal->delete();

        return response()->json([
            'message' => 'Inventory withdrawal deleted successfully.',
        ]);
    }
}
