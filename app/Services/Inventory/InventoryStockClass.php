<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Services\DropdownClass;

class InventoryStockClass
{
    public function __construct(public DropdownClass $dropdown)
    {
    }

    public function lists($request)
    {
        $count = max((int) $request->input('count', 10), 1);

        $data = InventoryStock::with(['inventory.category', 'inventory.unit', 'location'])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('status', 'like', "%{$keyword}%")
                        ->orWhereHas('inventory', function ($inventory) use ($keyword) {
                            $inventory->where('name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('location', function ($location) use ($keyword) {
                            $location->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('location_id'), function ($query) use ($request) {
                $query->where('location_id', $request->input('location_id'));
            })
            ->orderByDesc('last_updated')
            ->orderByDesc('id')
            ->paginate($count);

        return InventoryStockResource::collection($data);
    }

    public function indexData(): array
    {
        return [
            'dropdowns' => [
                'locations' => $this->dropdown->dropdowns('Location'),
                'categories' => $this->dropdown->dropdowns('Inventory Category'),
                'units' => $this->dropdown->dropdowns('Unit'),
            ],
            'inventories' => InventoryItemResource::collection(
                Inventory::with(['category:id,name', 'unit:id,name'])
                    ->orderBy('name')
                    ->get(['id', 'name', 'category_id', 'unit_id'])
            ),
        ];
    }

    public function save($request): array
    {
        $inventoryId = $request->inventory_id;

        if ($request->inventory_id === '__NEW__') {
            $inventory = Inventory::create([
                'name' => $request->new_inventory_name,
                'description' => $request->new_inventory_description,
                'category_id' => $request->new_category_id,
                'unit_id' => $request->new_unit_id,
                'min_stock_level' => $request->new_min_stock_level ?? 0,
            ]);

            $inventoryId = $inventory->id;
        }

        $stock = InventoryStock::create([
            'inventory_id' => $inventoryId,
            'location_id' => $request->location_id,
            'quantity' => $request->quantity,
            'status' => $request->status,
            'last_updated' => $request->last_updated ?? now(),
        ]);

        $stock->load(['inventory.category', 'inventory.unit', 'location']);

        return [
            'data' => new InventoryStockResource($stock),
            'message' => 'Inventory stock created successfully.',
            'info' => "You've successfully created an inventory stock.",
        ];
    }

    public function update($request, InventoryStock $inventoryStock): array
    {
        $inventoryStock->update([
            'inventory_id' => $request->inventory_id,
            'location_id' => $request->location_id,
            'quantity' => $request->quantity,
            'status' => $request->status,
            'last_updated' => $request->last_updated ?? $inventoryStock->last_updated ?? now(),
        ]);

        $inventoryStock->load(['inventory.category', 'inventory.unit', 'location']);

        return [
            'data' => new InventoryStockResource($inventoryStock),
            'message' => 'Inventory stock updated successfully.',
            'info' => "You've successfully updated the inventory stock.",
        ];
    }

    public function delete(InventoryStock $inventoryStock): array
    {
        $id = $inventoryStock->id;
        $inventoryStock->delete();

        return [
            'data' => ['id' => $id],
            'message' => 'Inventory stock deleted successfully.',
            'info' => "You've successfully deleted the inventory stock.",
        ];
    }
}
