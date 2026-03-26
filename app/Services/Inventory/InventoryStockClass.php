<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
use App\Models\Inventory;
use App\Models\InventoryReceivingTransfer;
use App\Models\InventoryStock;
use App\Models\InventoryWithdrawal;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\ProcurementItem;
use App\Models\ProcurementNoaPo;
use App\Services\DropdownClass;
use Illuminate\Support\Facades\Auth;

class InventoryStockClass
{
    public function __construct(public DropdownClass $dropdown)
    {
    }

    public function lists($request)
    {
        $count = max((int) $request->input('count', 10), 1);
        $completedStatusId = ListStatus::getID('Completed', 'Procurement');
        $receivedItemNames = ProcurementNoaPo::with(['noa.items.item.item:id,item_description'])
            ->where('status_id', $completedStatusId)
            ->get()
            ->flatMap(fn ($po) => collect($po->noa?->items ?? []))
            ->map(fn ($item) => trim((string) $item->item?->item?->item_description))
            ->filter()
            ->unique()
            ->values();

        if ($receivedItemNames->isEmpty()) {
            return InventoryStockResource::collection(collect());
        }

        $data = InventoryStock::with(['inventory.category', 'inventory.unit', 'location'])
            ->whereHas('inventory', function ($query) use ($receivedItemNames) {
                $query->whereIn('name', $receivedItemNames->all());
            })
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

    public function transferReceiving($request): array
    {
        $receiving = ProcurementNoaPo::with(['noa.items.item.item_unit_type', 'place_of_delivery:id,name'])
            ->findOrFail((int) $request->input('receiving_id'));

        $result = $this->transferCompletedPoItemsToInventory($receiving);

        if ($result['transferred'] === 0) {
            return [
                'data' => $result,
                'message' => 'No items were transferred.',
                'info' => $result['skipped']
                    ? 'This receiving was already transferred or its items could not be mapped to inventory units.'
                    : 'There are no completed procurement items available to transfer.',
                'status' => 'warning',
            ];
        }

        return [
            'data' => $result,
            'message' => 'Receiving transferred to inventory successfully.',
            'info' => "Transferred {$result['transferred']} item(s) from {$receiving->code} to inventory stock.",
            'status' => 'success',
        ];
    }

    private function transferCompletedPoItemsToInventory(ProcurementNoaPo $po): array
    {
        $categoryId = $this->defaultInventoryCategoryId();

        if (!$categoryId) {
            return ['transferred' => 0, 'skipped' => 0];
        }

        $procurementItems = collect($po->noa?->items ?? [])
            ->map(fn ($item) => $item->item)
            ->filter();

        $transferred = 0;
        $skipped = 0;

        foreach ($procurementItems as $item) {
            $existingTransfer = InventoryReceivingTransfer::where('po_id', $po->id)
                ->where('procurement_item_id', $item->id)
                ->exists();

            if ($existingTransfer) {
                $skipped++;
                continue;
            }

            $unitId = $this->resolveInventoryUnitIdFromProcurementItem($item);
            if (!$unitId) {
                $skipped++;
                continue;
            }

            $itemName = trim((string) $item->item_description);
            if ($itemName === '') {
                $itemName = 'Procurement Item #' . $item->id;
            }

            $inventory = Inventory::firstOrCreate(
                [
                    'name' => $itemName,
                    'unit_id' => $unitId,
                ],
                [
                    'description' => $item->item_description,
                    'category_id' => $categoryId,
                    'min_stock_level' => 0,
                ]
            );

            $stock = InventoryStock::firstOrNew([
                'inventory_id' => $inventory->id,
                'location_id' => $po->place_of_delivery_id,
            ]);

            $currentQuantity = (float) ($stock->quantity ?? 0);
            $incomingQuantity = (float) ($item->item_quantity ?? 0);
            $newQuantity = $currentQuantity + $incomingQuantity;

            $stock->quantity = $newQuantity;
            $stock->status = $this->resolveInventoryStockStatus($newQuantity, (float) $inventory->min_stock_level);
            $stock->last_updated = $po->updated_at ?? now();
            $stock->save();

            InventoryReceivingTransfer::create([
                'po_id' => $po->id,
                'procurement_item_id' => $item->id,
                'inventory_id' => $inventory->id,
                'inventory_stock_id' => $stock->id,
                'quantity' => $incomingQuantity,
                'transferred_at' => now(),
            ]);

            $transferred++;
        }

        return ['transferred' => $transferred, 'skipped' => $skipped];
    }

    private function resolveInventoryUnitIdFromProcurementItem(ProcurementItem $item): ?int
    {
        $unitType = $item->item_unit_type;
        if (!$unitType) {
            return null;
        }

        $candidates = collect([
            $unitType->name_short ?? null,
            $unitType->name_long ?? null,
        ])->filter()->map(fn ($name) => trim((string) $name))->filter()->values();

        foreach ($candidates as $candidate) {
            $unit = ListDropdown::where('classification', 'Unit')
                ->where(function ($query) use ($candidate) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower($candidate)])
                        ->orWhereRaw('LOWER(others) = ?', [strtolower($candidate)]);
                })
                ->first();

            if ($unit) {
                return (int) $unit->id;
            }
        }

        return null;
    }

    private function defaultInventoryCategoryId(): ?int
    {
        return ListDropdown::where('classification', 'Inventory Category')
            ->orderBy('id')
            ->value('id');
    }

    private function resolveInventoryStockStatus(float $quantity, float $minStockLevel): string
    {
        if ($quantity <= 0) {
            return 'out';
        }

        if ($quantity <= $minStockLevel) {
            return 'low';
        }

        return 'available';
    }

    public function receivings($request)
    {
        $count = max((int) $request->input('count', 10), 1);
        $completedStatusId = ListStatus::getID('Completed', 'Procurement');

        $data = ProcurementNoaPo::with([
                'status:id,name',
                'place_of_delivery:id,name',
                'noa.procurement:id,code,title,purpose',
                'noa.procurement_quotation.supplier:id,name',
            ])
            ->where('status_id', $completedStatusId)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($count);

        return InventoryReceivingResource::collection($data);
    }

    public function withdrawals($request)
    {
        $count = max((int) $request->input('count', 10), 1);

        $data = InventoryWithdrawal::with(['requested_by.profile', 'location'])
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate($count);

        return InventoryWithdrawalResource::collection($data);
    }

    public function receivingWithdrawalItems($request)
    {
        $receivingId = (int) $request->input('receiving_id');

        $receiving = ProcurementNoaPo::with([
                'place_of_delivery:id,name',
                'noa.items.item.item:id,item_description',
            ])
            ->findOrFail($receivingId);

        $itemNames = collect($receiving->noa?->items ?? [])
            ->map(fn ($item) => trim((string) $item->item?->item?->item_description))
            ->filter()
            ->unique()
            ->values();

        if ($itemNames->isEmpty()) {
            return InventoryStockResource::collection(collect());
        }

        $stocks = InventoryStock::with(['inventory.category', 'inventory.unit', 'location'])
            ->where('location_id', $receiving->place_of_delivery_id)
            ->where('quantity', '>', 0)
            ->whereHas('inventory', function ($query) use ($itemNames) {
                $query->whereIn('name', $itemNames->all());
            })
            ->orderByDesc('last_updated')
            ->orderByDesc('id')
            ->get()
            ->sortBy(fn ($stock) => array_search($stock->inventory?->name, $itemNames->all(), true))
            ->values();

        return InventoryStockResource::collection($stocks);
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
            'receivings' => InventoryReceivingResource::collection(
                ProcurementNoaPo::with([
                        'status:id,name',
                        'place_of_delivery:id,name',
                        'noa.procurement:id,code,title,purpose',
                        'noa.procurement_quotation.supplier:id,name',
                    ])
                    ->where('status_id', ListStatus::getID('Completed', 'Procurement'))
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->paginate(10)
            ),
            'withdrawals' => InventoryWithdrawalResource::collection(
                InventoryWithdrawal::with(['requested_by.profile', 'location'])
                    ->orderByDesc('released_at')
                    ->orderByDesc('id')
                    ->paginate(10)
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
        $inventoryStock->loadMissing(['inventory', 'location']);

        $previousQuantity = (float) ($inventoryStock->quantity ?? 0);
        $newQuantity = (float) $request->quantity;
        $releasedQuantity = $previousQuantity > $newQuantity ? ($previousQuantity - $newQuantity) : 0;

        if ($releasedQuantity > 0) {
            $this->recordWithdrawal($inventoryStock, $releasedQuantity, $request);
        }

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

    private function recordWithdrawal(InventoryStock $inventoryStock, float $releasedQuantity, $request): void
    {
        $user = Auth::user();
        $inventoryStock->loadMissing(['inventory', 'location']);

        InventoryWithdrawal::create([
            'reference_no' => InventoryWithdrawal::generateReferenceNumber(),
            'inventory_id' => $inventoryStock->inventory_id,
            'inventory_stock_id' => $inventoryStock->id,
            'location_id' => $inventoryStock->location_id,
            'requested_by_id' => $request->input('requested_by_id') ?: $user?->id,
            'item_name' => $inventoryStock->inventory?->name ?? 'Inventory Item',
            'quantity' => $releasedQuantity,
            'released_at' => $request->last_updated ?? now(),
            'status' => 'Released',
            'remarks' => $request->input('withdrawal_remarks') ?: 'Recorded automatically from inventory stock reduction.',
        ]);
    }
}






