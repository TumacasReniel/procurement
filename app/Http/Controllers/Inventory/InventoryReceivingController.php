<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryReceivingRequest;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Models\InventoryReceiving;
use Illuminate\Http\Request;

class InventoryReceivingController extends Controller
{
    public function index(Request $request)
    {
        $receivings = InventoryReceiving::with(['item', 'approvedBy.profile', 'status'])
            ->orderByDesc('id')
            ->paginate((int) $request->input('count', 10));

        return InventoryReceivingResource::collection($receivings);
    }

    public function store(InventoryReceivingRequest $request)
    {
        $receiving = InventoryReceiving::create($request->validated());

        return response()->json([
            'data' => new InventoryReceivingResource($receiving->load(['item', 'approvedBy.profile', 'status'])),
            'message' => 'Inventory receiving created successfully.',
        ]);
    }

    public function update(InventoryReceivingRequest $request, InventoryReceiving $inventory_receiving)
    {
        $inventory_receiving->update($request->validated());

        return response()->json([
            'data' => new InventoryReceivingResource($inventory_receiving->load(['item', 'approvedBy.profile', 'status'])),
            'message' => 'Inventory receiving updated successfully.',
        ]);
    }

    public function destroy(InventoryReceiving $inventory_receiving)
    {
        $inventory_receiving->delete();

        return response()->json([
            'message' => 'Inventory receiving deleted successfully.',
        ]);
    }
}
