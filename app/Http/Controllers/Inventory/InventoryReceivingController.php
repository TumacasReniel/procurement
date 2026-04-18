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
        if ($request->header('X-Inertia')) {
            return redirect('/inventory-stocks?tab=receivings');
        }

        if (!$request->wantsJson() && !$request->ajax() && !$request->boolean('json')) {
            return redirect('/inventory-stocks?tab=receivings');
        }

        $receivings = InventoryReceiving::with(['item', 'approvedBy.profile', 'status'])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('remarks', 'like', "%{$keyword}%")
                        ->orWhereHas('item', function ($item) use ($keyword) {
                            $item->where('name', 'like', "%{$keyword}%")
                                ->orWhere('code', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('approvedBy', function ($user) use ($keyword) {
                            $user->where('username', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('status', function ($status) use ($keyword) {
                            $status->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(max((int) $request->input('count', 10), 1));

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
