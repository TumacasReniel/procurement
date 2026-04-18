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
        if ($request->header('X-Inertia')) {
            return redirect('/inventory-stocks?tab=withdrawals');
        }

        if (!$request->wantsJson() && !$request->ajax() && !$request->boolean('json')) {
            return redirect('/inventory-stocks?tab=withdrawals');
        }

        $withdrawals = InventoryWithdrawal::with(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('remarks', 'like', "%{$keyword}%")
                        ->orWhereHas('item', function ($item) use ($keyword) {
                            $item->where('name', 'like', "%{$keyword}%")
                                ->orWhere('code', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('requestedBy', function ($user) use ($keyword) {
                            $user->where('username', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('approvedBy', function ($user) use ($keyword) {
                            $user->where('username', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('status', function ($status) use ($keyword) {
                            $status->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate(max((int) $request->input('count', 10), 1));

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
