<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryItemRequest;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $items = InventoryItem::with(['stock', 'category'])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('id')
            ->paginate((int) $request->input('count', 10));

        return InventoryItemResource::collection($items);
    }

    public function store(InventoryItemRequest $request)
    {
        $item = InventoryItem::create($request->validated());

        return response()->json([
            'data' => new InventoryItemResource($item->load(['stock', 'category'])),
            'message' => 'Inventory item created successfully.',
        ]);
    }

    public function update(InventoryItemRequest $request, InventoryItem $inventory_item)
    {
        $inventory_item->update($request->validated());

        return response()->json([
            'data' => new InventoryItemResource($inventory_item->load(['stock', 'category'])),
            'message' => 'Inventory item updated successfully.',
        ]);
    }

    public function destroy(InventoryItem $inventory_item)
    {
        $inventory_item->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully.',
        ]);
    }
}
