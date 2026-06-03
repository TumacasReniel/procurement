<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryRisRequest;
use App\Http\Resources\Inventory\InventoryRisResource;
use App\Models\InventoryRis;
use App\Models\InventoryRisItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryRisController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryRis::with(['requestedBy.profile', 'status'])
            ->withCount('items')
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $kw = trim((string) $request->input('keyword'));
                $q->where(function ($inner) use ($kw) {
                    $inner->where('ris_no', 'like', "%{$kw}%")
                          ->orWhere('division', 'like', "%{$kw}%")
                          ->orWhere('purpose', 'like', "%{$kw}%");
                });
            })
            ->orderByDesc('ris_date')
            ->orderByDesc('id');

        $perPage = max((int) $request->input('count', 10), 1);

        return InventoryRisResource::collection($query->paginate($perPage));
    }

    public function show(InventoryRis $inventory_ri)
    {
        return new InventoryRisResource($inventory_ri->load([
            'items.item',
            'requestedBy.profile',
            'approvedBy.profile',
            'issuedBy.profile',
            'receivedBy.profile',
            'status',
        ]));
    }

    public function store(InventoryRisRequest $request)
    {
        $ris = DB::transaction(function () use ($request) {
            $ris = InventoryRis::create($request->safe()->except('items'));

            foreach ((array) $request->input('items', []) as $line) {
                InventoryRisItem::create([
                    'ris_id'             => $ris->id,
                    'item_id'            => $line['item_id'],
                    'unit_of_issue'      => $line['unit_of_issue'] ?? null,
                    'quantity_requested' => $line['quantity_requested'] ?? 0,
                    'quantity_issued'    => $line['quantity_issued'] ?? 0,
                    'remarks'            => $line['remarks'] ?? null,
                ]);
            }

            return $ris;
        });

        return response()->json([
            'data'    => new InventoryRisResource($ris->load(['items.item', 'status'])),
            'message' => 'RIS created successfully.',
        ], 201);
    }

    public function update(InventoryRisRequest $request, InventoryRis $inventory_ri)
    {
        DB::transaction(function () use ($request, $inventory_ri) {
            $inventory_ri->update($request->safe()->except('items'));

            $inventory_ri->items()->delete();
            foreach ((array) $request->input('items', []) as $line) {
                InventoryRisItem::create([
                    'ris_id'             => $inventory_ri->id,
                    'item_id'            => $line['item_id'],
                    'unit_of_issue'      => $line['unit_of_issue'] ?? null,
                    'quantity_requested' => $line['quantity_requested'] ?? 0,
                    'quantity_issued'    => $line['quantity_issued'] ?? 0,
                    'remarks'            => $line['remarks'] ?? null,
                ]);
            }
        });

        return response()->json([
            'data'    => new InventoryRisResource($inventory_ri->fresh()->load(['items.item', 'status'])),
            'message' => 'RIS updated successfully.',
        ]);
    }

    public function destroy(InventoryRis $inventory_ri)
    {
        $id = $inventory_ri->id;
        $inventory_ri->delete();

        return response()->json(['data' => ['id' => $id], 'message' => 'RIS deleted.']);
    }
}
