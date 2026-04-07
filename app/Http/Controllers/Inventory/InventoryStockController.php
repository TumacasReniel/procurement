<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryStockRequest;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryLookupResource;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryReceiving;
use App\Models\InventoryStock;
use App\Models\InventoryWithdrawal;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryStockController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax() || $request->boolean('json')) {
            $stocks = InventoryStock::with('inventory')
                ->when($request->filled('keyword'), function ($query) use ($request) {
                    $keyword = trim((string) $request->input('keyword'));
                    $query->where(function ($inner) use ($keyword) {
                        $inner->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhereHas('inventory', function ($inventory) use ($keyword) {
                                $inventory->where('name', 'like', "%{$keyword}%");
                            });
                    });
                })
                ->orderByDesc('id')
                ->paginate((int) $request->input('count', 10));

            return InventoryStockResource::collection($stocks);
        }

        return Inertia::render('Modules/Inventory/Index', [
            'dropdowns' => [
                'locations' => ListDropdown::where('classification', 'Location')->get(['id', 'name']),
                'categories' => ListDropdown::where('classification', 'Inventory Category')->get(['id', 'name']),
                'statuses' => ListStatus::where('is_active', 1)->get(['id', 'name']),
            ],
            'users' => User::with('profile')->get()->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->profile?->fullname ?? $user->username,
            ]),
            'inventories' => InventoryLookupResource::collection(
                Inventory::with(['category:id,name', 'unit:id,name'])->orderBy('name')->get()
            ),
            'stocks' => InventoryStockResource::collection(InventoryStock::with('inventory')->orderByDesc('id')->paginate(10)),
            'items' => InventoryItemResource::collection(InventoryItem::with(['stock', 'category'])->orderByDesc('id')->paginate(10)),
            'receivings' => InventoryReceivingResource::collection(InventoryReceiving::with(['item', 'approvedBy.profile', 'status'])->orderByDesc('id')->paginate(10)),
            'withdrawals' => InventoryWithdrawalResource::collection(InventoryWithdrawal::with(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])->orderByDesc('id')->paginate(10)),
        ]);
    }

    public function store(InventoryStockRequest $request)
    {
        $stock = InventoryStock::create([
            'code' => $request->code,
            'name' => $request->name,
            'inventory_id' => $request->inventory_id,
            'entry_date' => $request->entry_date,
        ]);

        return response()->json([
            'data' => new InventoryStockResource($stock->load('inventory')),
            'message' => 'Inventory stock created successfully.',
        ]);
    }

    public function update(InventoryStockRequest $request, InventoryStock $inventory_stock)
    {
        $inventory_stock->update($request->validated());

        return response()->json([
            'data' => new InventoryStockResource($inventory_stock->load('inventory')),
            'message' => 'Inventory stock updated successfully.',
        ]);
    }

    public function destroy(InventoryStock $inventory_stock)
    {
        $inventory_stock->delete();

        return response()->json([
            'message' => 'Inventory stock deleted successfully.',
        ]);
    }
}
