<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryIcsResource;
use App\Http\Resources\Inventory\InventoryItemPropertyResource;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryParResource;
use App\Http\Resources\Inventory\InventoryPhysicalCountResource;
use App\Http\Resources\Inventory\InventoryReportResource;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Http\Resources\Inventory\InventoryRisResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
use App\Models\InventoryIcs;
use App\Models\InventoryIcsItem;
use App\Models\InventoryItem;
use App\Models\InventoryItemProperty;
use App\Models\InventoryReport;
use App\Models\InventoryReportTitle;
use App\Models\InventoryPar;
use App\Models\InventoryParItem;
use App\Models\InventoryPhysicalCount;
use App\Models\InventoryPhysicalCountItem;
use App\Models\InventoryRis;
use App\Models\InventoryRisItem;
use App\Models\InventoryReceiving;
use App\Models\InventoryReceivingTransfer;
use App\Models\InventoryStock;
use App\Models\InventoryStockAdjustment;
use App\Models\InventoryStockDrain;
use App\Models\OrgChart;
use App\Models\ProcurementNoaPo;
use App\Models\InventoryWithdrawal;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\ListUnit;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryStockClass
{
    public function indexData(Request $request): array
    {
        return [
            'dropdowns' => [
                'categories' => ListDropdown::where('classification', 'Item Category')
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'statuses'   => $this->inventoryStatuses(),
                'unitTypes'  => UnitType::orderBy('name_long')->get(['id', 'name_short', 'name_long']),
                'reportCategories' => ListDropdown::where('classification', 'Report Category')
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'reportTitles' => InventoryReportTitle::where('is_active', 1)
                    ->orderBy('name')
                    ->get(['id', 'name', 'category_id']),
            ],
            'reports' => $this->reports(),
            'users' => User::with('profile')
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->profile?->fullname ?? $user->username,
                ])
                ->sortBy('name')
                ->values(),
            'stockOptions' => InventoryStock::with('item:id,code,name')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($stock) => [
                    'id'      => $stock->id,
                    'item_id' => $stock->item_id,
                    'name'    => $stock->item?->name ?? '—',
                    'code'    => $stock->item?->code ?? '—',
                ])
                ->values(),
            'itemOptions' => InventoryItem::orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                ])
                ->values(),
            'stocks' => $this->stocks($request),
            'items' => $this->items($request),
            'receivings' => $this->receivings($request),
            'withdrawals' => $this->withdrawals($request),
            'fund_clusters' => ListDropdown::where('classification', 'Fund Cluster')
                ->orderBy('name')
                ->get(['id', 'name']),
            'divisions' => ListDropdown::where('classification', 'Division')
                ->orderBy('name')
                ->get(['id', 'name']),
            'units' => ListUnit::with('responsibility_center:id,list_unit_id,code')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'division_id'])
                ->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'division_id' => $unit->division_id,
                    'responsibility_center_code' => $unit->responsibility_center?->code,
                ])
                ->values(),
            'risDefaults' => [
                'pending_status_id' => (string) (ListStatus::where('name', 'Pending')->where('classification', 'Inventory')->value('id') ?? ''),
                'regional_director_id' => (string) (User::whereHasActiveRole('Regional Director')->value('id') ?? ''),
                'supply_officer_id' => (string) (User::whereHasActiveRole('Supply Officer')->value('id') ?? ''),
            ],
        ];
    }

    public function stocks(Request $request)
    {
        return InventoryStockResource::collection(
            $this->stocksQuery($request)->paginate($this->perPage($request))
        );
    }

    public function items(Request $request)
    {
        return InventoryItemResource::collection(
            $this->itemsQuery($request)->paginate($this->perPage($request))
        );
    }

    public function receivings(Request $request)
    {
        return InventoryReceivingResource::collection(
            $this->receivingsQuery($request)->paginate($this->perPage($request))
        );
    }

    public function withdrawals(Request $request)
    {
        return InventoryWithdrawalResource::collection(
            $this->withdrawalsQuery($request)->paginate($this->perPage($request))
        );
    }

    public function saveStock($request): array
    {
        $stock = InventoryStock::create($request->validated());

        return $this->stockResult(
            $stock,
            'Stock entry added successfully.',
            "You've successfully added a stock entry."
        );
    }

    public function updateStock($request, InventoryStock $stock): array
    {
        $stock->update($request->validated());

        return $this->stockResult(
            $stock,
            'Inventory stock updated successfully.',
            "You've successfully updated the stock group."
        );
    }

    public function deleteStock(InventoryStock $stock): array
    {
        $id = $stock->id;
        $stock->delete();

        return $this->deleteResult($id, 'Inventory stock deleted successfully.', "You've successfully deleted the stock group.");
    }

    public function saveItem($request): array
    {
        $item = InventoryItem::create($request->validated());

        return $this->itemResult(
            $item,
            'Inventory item created successfully.',
            "You've successfully created an inventory item."
        );
    }

    public function updateItem($request, InventoryItem $item): array
    {
        $item->update($request->validated());

        return $this->itemResult(
            $item,
            'Inventory item updated successfully.',
            "You've successfully updated the inventory item."
        );
    }

    public function deleteItem(InventoryItem $item): array
    {
        $id = $item->id;
        $item->delete();

        return $this->deleteResult($id, 'Inventory item deleted successfully.', "You've successfully deleted the inventory item.");
    }

    public function saveReceiving($request): array
    {
        $data = $request->validated();

        $stock = $this->resolveReceivingStock((int) $data['item_id'], $data['stock_id'] ?? null);
        $stock->quantity = (float) $stock->quantity + (float) $data['quantity'];
        $stock->save();

        $data['inventory_stock_id'] = $stock->id;
        $data['unit_cost'] = $stock->unit_cost;

        $receiving = InventoryReceiving::create($data);

        return $this->receivingResult(
            $receiving,
            'Inventory receiving created successfully.',
            "You've successfully logged a receiving record."
        );
    }

    public function updateReceiving($request, InventoryReceiving $receiving): array
    {
        $data = $request->validated();

        // Reverse the old quantity from whichever stock row this receiving originally hit.
        if ($receiving->inventory_stock_id) {
            $oldStock = InventoryStock::where('id', $receiving->inventory_stock_id)->lockForUpdate()->first();
            if ($oldStock) {
                $oldStock->quantity = max((float) $oldStock->quantity - (float) $receiving->quantity, 0);
                $oldStock->save();
            }
        }

        $stock = $this->resolveReceivingStock((int) $data['item_id'], $data['stock_id'] ?? null);
        $stock->quantity = (float) $stock->quantity + (float) $data['quantity'];
        $stock->save();

        $data['inventory_stock_id'] = $stock->id;
        $data['unit_cost'] = $stock->unit_cost;

        $receiving->update($data);

        return $this->receivingResult(
            $receiving,
            'Inventory receiving updated successfully.',
            "You've successfully updated the receiving record."
        );
    }

    public function deleteReceiving(InventoryReceiving $receiving): array
    {
        if ($receiving->inventory_stock_id) {
            $stock = InventoryStock::where('id', $receiving->inventory_stock_id)->lockForUpdate()->first();
            if ($stock) {
                $stock->quantity = max((float) $stock->quantity - (float) $receiving->quantity, 0);
                $stock->save();
            }
        }

        $id = $receiving->id;
        $receiving->delete();

        return $this->deleteResult($id, 'Inventory receiving deleted successfully.', "You've successfully deleted the receiving record.");
    }

    /**
     * Resolve (or create) the InventoryStock row a manual receiving should land on:
     * an explicit stock_id if given, else the item's highest-quantity stock row, else
     * a brand-new one — mirroring the no-stock-row fallback used elsewhere in this class.
     */
    private function resolveReceivingStock(int $itemId, ?int $stockId): InventoryStock
    {
        $stock = $stockId
            ? InventoryStock::where('id', $stockId)->lockForUpdate()->first()
            : InventoryStock::where('item_id', $itemId)->orderByDesc('quantity')->lockForUpdate()->first();

        if ($stock) {
            return $stock;
        }

        return InventoryStock::create([
            'item_id'  => $itemId,
            'quantity' => 0,
            'unit_id'  => UnitType::orderBy('id')->value('id') ?? 1,
        ]);
    }

    public function saveWithdrawal($request): array
    {
        $withdrawal = InventoryWithdrawal::create($request->validated());

        return $this->withdrawalResult(
            $withdrawal,
            'Inventory withdrawal created successfully.',
            "You've successfully logged a withdrawal record."
        );
    }

    public function updateWithdrawal($request, InventoryWithdrawal $withdrawal): array
    {
        $oldStatusId  = $withdrawal->status_id;
        $newStatusId  = $request->input('status_id');
        $completedId  = ListStatus::getID('Completed', 'Inventory');

        $withdrawal->update($request->validated());

        // Deduct stock once when withdrawal transitions to Completed
        if ($completedId && (int) $newStatusId === (int) $completedId && (int) $oldStatusId !== (int) $completedId) {
            $this->deductStockForWithdrawal($withdrawal->fresh());
        }

        return $this->withdrawalResult(
            $withdrawal,
            'Inventory withdrawal updated successfully.',
            "You've successfully updated the withdrawal record."
        );
    }

    public function deleteWithdrawal(InventoryWithdrawal $withdrawal): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ((int) $withdrawal->status_id === $completedId) {
            throw new \Exception('Completed withdrawals cannot be deleted. Use void instead.');
        }

        $id = $withdrawal->id;
        $withdrawal->delete();

        return $this->deleteResult($id, 'Inventory withdrawal deleted successfully.', "You've successfully deleted the withdrawal record.");
    }

    public function risList(Request $request)
    {
        return InventoryRisResource::collection(
            InventoryRis::with(['requestedBy.profile', 'status'])
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
                ->orderByDesc('id')
                ->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    public function saveRis(Request $request): array
    {
        $pendingId = ListStatus::getID('Pending', 'Inventory');

        $ris = InventoryRis::create(array_merge(
            collect($request->safe()->except('items'))->toArray(),
            ['status_id' => $pendingId]
        ));

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

        return [
            'data'    => new InventoryRisResource($ris->load(['items.item', 'status'])),
            'message' => 'RIS created successfully.',
            'info'    => "RIS {$ris->ris_no} has been created.",
        ];
    }

    public function updateRis(Request $request, InventoryRis $ris): array
    {
        $completedStatusId = (int) ListStatus::getID('Completed', 'Inventory');
        $approvedStatusId  = (int) ListStatus::getID('Approved', 'Inventory');
        $oldStatusId       = (int) $ris->status_id;
        $newStatusId       = (int) $request->input('status_id');
        $isAlreadyCompleted = $completedStatusId && $oldStatusId === $completedStatusId;

        if ($completedStatusId && $newStatusId === $completedStatusId) {
            if (! $request->input('approved_by_id')) {
                throw new \Exception('RIS must be approved before it can be completed.');
            }
            if (! $request->input('issued_by_id')) {
                throw new \Exception('Issued by is required to complete the RIS.');
            }
            $hasIssuedQty = collect((array) $request->input('items', []))->sum('quantity_issued') > 0;
            if (! $hasIssuedQty) {
                throw new \Exception('At least one item must have an issued quantity before completing the RIS.');
            }
        }

        if ($approvedStatusId && $newStatusId === $approvedStatusId && ! $request->input('approved_by_id')) {
            throw new \Exception('Approved by is required to approve the RIS.');
        }

        $ris->update($request->safe()->except('items'));

        if (! $isAlreadyCompleted) {
            $ris->items()->delete();
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
        }

        if ($completedStatusId && $newStatusId === $completedStatusId && $oldStatusId !== $completedStatusId) {
            $this->deductStockForRis($ris->fresh());
        }

        return [
            'data'    => new InventoryRisResource($ris->fresh()->load(['items.item', 'status'])),
            'message' => 'RIS updated successfully.',
            'info'    => "RIS {$ris->ris_no} has been updated.",
        ];
    }

    public function deleteRis(InventoryRis $ris): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ((int) $ris->status_id === $completedId) {
            throw new \Exception('Completed RIS cannot be deleted. Use void instead.');
        }

        $id = $ris->id;
        $ris->delete();

        return [
            'data'    => ['id' => $id],
            'message' => 'RIS deleted.',
            'info'    => null,
        ];
    }

    public function voidRis(InventoryRis $ris): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        $cancelledId = (int) ListStatus::getID('Cancelled', 'Inventory');

        if ((int) $ris->status_id !== $completedId) {
            throw new \Exception('Only completed RIS records can be voided.');
        }
        if (! $cancelledId) {
            throw new \Exception('Cancelled status not found.');
        }

        foreach ($ris->items as $risItem) {
            $issued = (float) $risItem->quantity_issued;
            if ($issued <= 0) {
                continue;
            }

            $this->restoreStock($risItem->item_id, $issued, 'ris_item', $risItem->id);
        }

        $ris->update(['status_id' => $cancelledId]);

        return [
            'data'    => new InventoryRisResource($ris->fresh()->load(['items.item', 'status'])),
            'message' => 'RIS voided and stock restored.',
            'info'    => null,
        ];
    }

    public function deductStockForRis(InventoryRis $ris): void
    {
        foreach ($ris->items()->with('item')->get() as $risItem) {
            $remaining = (float) $risItem->quantity_issued;
            if ($remaining <= 0) {
                continue;
            }

            $this->drainStock($risItem->item_id, $remaining, 'ris_item', $risItem->id);
        }
    }

    public function deductStockForWithdrawal(InventoryWithdrawal $withdrawal): void
    {
        $qty = (float) $withdrawal->quantity;
        if ($qty <= 0) {
            return;
        }

        $value = $this->drainStock($withdrawal->inventory_id, $qty, 'withdrawal', $withdrawal->id);
        $withdrawal->unit_cost = round($value / $qty, 4);
        $withdrawal->save();
    }

    /**
     * Drain quantity from an item's stock rows oldest-first (FIFO) and record which
     * batch(es) absorbed the deduction in inventory_stock_drains, keyed by the caller's
     * source (e.g. a RIS line item or a withdrawal), so a later void/restore can put the
     * exact quantity back on the exact batches instead of guessing. Returns the total
     * value drained (sum of qty * unit_cost across the batches touched).
     */
    public function drainStock(int $itemId, float $needed, string $sourceType, int $sourceId): float
    {
        $stocks = InventoryStock::where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('id') // FIFO: oldest stock first
            ->lockForUpdate()
            ->get();

        $available = (float) $stocks->sum('quantity');

        if ($needed > $available + 0.0001) {
            $item = InventoryItem::find($itemId);
            $name = $item?->name ?? "item #{$itemId}";
            throw new \Exception("Insufficient stock for {$name}: requested {$needed}, only {$available} on hand.");
        }

        $totalValue = 0.0;

        foreach ($stocks as $stock) {
            if ($needed <= 0) {
                break;
            }

            $rowQty = (float) $stock->quantity;
            $deduct = min($rowQty, $needed);
            $stock->quantity = $rowQty - $deduct;
            $stock->save();
            $needed -= $deduct;

            InventoryStockDrain::create([
                'inventory_stock_id' => $stock->id,
                'item_id'            => $itemId,
                'source_type'        => $sourceType,
                'source_id'          => $sourceId,
                'quantity'           => $deduct,
                'unit_cost'          => $stock->unit_cost,
            ]);

            $totalValue += $deduct * (float) ($stock->unit_cost ?? 0);
        }

        return $totalValue;
    }

    /**
     * Reverse a prior drainStock() call for the given source, restoring quantity to the
     * exact batches it was taken from (not just "whichever stock row has the most on
     * hand"), so cost-basis stays correct. Falls back to a best-effort restore onto the
     * item's highest-quantity stock row only for legacy drains with no ledger rows.
     */
    protected function restoreStock(int $itemId, float $qty, string $sourceType, int $sourceId): void
    {
        $drains = InventoryStockDrain::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->lockForUpdate()
            ->get();

        if ($drains->isNotEmpty()) {
            foreach ($drains as $drain) {
                $stock = InventoryStock::where('id', $drain->inventory_stock_id)->lockForUpdate()->first();

                if ($stock) {
                    $stock->quantity = (float) $stock->quantity + (float) $drain->quantity;
                    $stock->save();
                } else {
                    // The batch itself was deleted since — recreate a minimal row so the
                    // restored quantity isn't silently lost.
                    InventoryStock::create([
                        'item_id'   => $drain->item_id,
                        'quantity'  => $drain->quantity,
                        'unit_id'   => UnitType::orderBy('id')->value('id') ?? 1,
                        'unit_cost' => $drain->unit_cost,
                    ]);
                }

                $drain->delete();
            }

            return;
        }

        // No ledger entries — legacy drain from before this feature existed. Best effort.
        if ($qty <= 0) {
            return;
        }

        $stock = InventoryStock::where('item_id', $itemId)
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->quantity = (float) $stock->quantity + $qty;
            $stock->save();

            return;
        }

        InventoryStock::create([
            'item_id'  => $itemId,
            'quantity' => $qty,
            'unit_id'  => UnitType::orderBy('id')->value('id') ?? 1,
        ]);
    }

    public function icsList(Request $request)
    {
        return InventoryIcsResource::collection(
            InventoryIcs::with(['issuedTo.profile', 'issuedBy.profile', 'status'])
                ->withCount('items')
                ->when($request->filled('keyword'), function ($q) use ($request) {
                    $kw = '%' . trim($request->input('keyword')) . '%';
                    $q->where('ics_no', 'like', $kw)->orWhere('remarks', 'like', $kw);
                })
                ->orderByDesc('ics_date')
                ->orderByDesc('id')
                ->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    public function saveIcs(Request $request): array
    {
        $ics = InventoryIcs::create($request->safe()->except('items'));
        foreach ((array) $request->input('items', []) as $line) {
            InventoryIcsItem::create(array_merge($line, ['ics_id' => $ics->id]));
        }
        return [
            'data'    => new InventoryIcsResource($ics->load(['items.item', 'issuedTo.profile', 'issuedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'ICS created successfully.',
            'info'    => "ICS {$ics->ics_no} has been created.",
        ];
    }

    public function updateIcs(Request $request, InventoryIcs $ics): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ($completedId && (int) $ics->status_id === $completedId) {
            throw new \Exception('Completed ICS records cannot be edited.');
        }

        $ics->update($request->safe()->except('items'));
        $ics->items()->delete();
        foreach ((array) $request->input('items', []) as $line) {
            InventoryIcsItem::create(array_merge($line, ['ics_id' => $ics->id]));
        }
        return [
            'data'    => new InventoryIcsResource($ics->fresh()->load(['items.item', 'issuedTo.profile', 'issuedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'ICS updated successfully.',
            'info'    => "ICS {$ics->ics_no} has been updated.",
        ];
    }

    public function deleteIcs(InventoryIcs $ics): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ($completedId && (int) $ics->status_id === $completedId) {
            throw new \Exception('Completed ICS records cannot be deleted.');
        }

        $id = $ics->id;
        $ics->delete();
        return ['data' => ['id' => $id], 'message' => 'ICS deleted.', 'info' => null];
    }

    public function parList(Request $request)
    {
        return InventoryParResource::collection(
            InventoryPar::with(['receivedBy.profile', 'issuedBy.profile', 'status'])
                ->withCount('items')
                ->when($request->filled('keyword'), function ($q) use ($request) {
                    $kw = '%' . trim($request->input('keyword')) . '%';
                    $q->where('par_no', 'like', $kw)->orWhere('remarks', 'like', $kw);
                })
                ->orderByDesc('par_date')
                ->orderByDesc('id')
                ->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    public function savePar(Request $request): array
    {
        $par = InventoryPar::create($request->safe()->except('items'));
        foreach ((array) $request->input('items', []) as $line) {
            InventoryParItem::create(array_merge($line, ['par_id' => $par->id]));
        }
        return [
            'data'    => new InventoryParResource($par->load(['items.item', 'receivedBy.profile', 'issuedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'PAR created successfully.',
            'info'    => "PAR {$par->par_no} has been created.",
        ];
    }

    public function updatePar(Request $request, InventoryPar $par): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ($completedId && (int) $par->status_id === $completedId) {
            throw new \Exception('Completed PAR records cannot be edited.');
        }

        $par->update($request->safe()->except('items'));
        $par->items()->delete();
        foreach ((array) $request->input('items', []) as $line) {
            InventoryParItem::create(array_merge($line, ['par_id' => $par->id]));
        }
        return [
            'data'    => new InventoryParResource($par->fresh()->load(['items.item', 'receivedBy.profile', 'issuedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'PAR updated successfully.',
            'info'    => "PAR {$par->par_no} has been updated.",
        ];
    }

    public function deletePar(InventoryPar $par): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ($completedId && (int) $par->status_id === $completedId) {
            throw new \Exception('Completed PAR records cannot be deleted.');
        }

        $id = $par->id;
        $par->delete();
        return ['data' => ['id' => $id], 'message' => 'PAR deleted.', 'info' => null];
    }

    public function physicalCountList(Request $request)
    {
        return InventoryPhysicalCountResource::collection(
            InventoryPhysicalCount::with(['countedBy.profile', 'verifiedBy.profile', 'status'])
                ->withCount('items')
                ->when($request->filled('keyword'), function ($q) use ($request) {
                    $kw = '%' . trim($request->input('keyword')) . '%';
                    $q->where(function ($inner) use ($kw) {
                        $inner->where('count_no', 'like', $kw)->orWhere('remarks', 'like', $kw);
                    });
                })
                ->orderByDesc('count_date')
                ->orderByDesc('id')
                ->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    public function savePhysicalCount(Request $request): array
    {
        $count = InventoryPhysicalCount::create([
            'count_date'    => $request->input('count_date'),
            'counted_by_id' => $request->input('counted_by_id', Auth::id()),
            'remarks'       => $request->input('remarks'),
            'status_id'     => $request->input('status_id'),
        ]);

        foreach (InventoryItem::with('stocks')->get() as $item) {
            $systemQty = $item->stocks->sum('quantity');
            InventoryPhysicalCountItem::create([
                'count_id'          => $count->id,
                'item_id'           => $item->id,
                'system_quantity'   => $systemQty,
                'physical_quantity' => $systemQty,
            ]);
        }

        return [
            'data'    => new InventoryPhysicalCountResource($count->load(['items.item', 'countedBy.profile', 'status'])),
            'message' => 'Physical count session created.',
            'info'    => "Count {$count->count_no} has been opened.",
        ];
    }

    public function updatePhysicalCount(Request $request, InventoryPhysicalCount $count): array
    {
        $oldStatusId = (int) $count->status_id;
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        $newStatusId = (int) $request->input('status_id', $oldStatusId);

        $count->update($request->only(['count_date', 'verified_by_id', 'approved_by_id', 'remarks', 'status_id']));

        foreach ((array) $request->input('items', []) as $line) {
            InventoryPhysicalCountItem::where('id', $line['id'])
                ->where('count_id', $count->id)
                ->update([
                    'physical_quantity' => $line['physical_quantity'],
                    'remarks'           => $line['remarks'] ?? null,
                ]);
        }

        if ($completedId && $newStatusId === $completedId && $oldStatusId !== $completedId) {
            $this->postPhysicalCountAdjustments($count->fresh());
        }

        return [
            'data'    => new InventoryPhysicalCountResource($count->fresh()->load(['items.item', 'countedBy.profile', 'status'])),
            'message' => 'Physical count updated.',
            'info'    => "Count {$count->count_no} has been updated.",
        ];
    }

    public function deletePhysicalCount(InventoryPhysicalCount $count): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        if ((int) $count->status_id === $completedId) {
            throw new \Exception('Completed physical counts cannot be deleted.');
        }

        $id = $count->id;
        $count->delete();
        return ['data' => ['id' => $id], 'message' => 'Physical count deleted.', 'info' => null];
    }

    public function voidWithdrawal(InventoryWithdrawal $withdrawal): array
    {
        $completedId = (int) ListStatus::getID('Completed', 'Inventory');
        $cancelledId = (int) ListStatus::getID('Cancelled', 'Inventory');

        if ((int) $withdrawal->status_id !== $completedId) {
            throw new \Exception('Only completed withdrawals can be voided.');
        }
        if (! $cancelledId) {
            throw new \Exception('Cancelled status not found.');
        }

        // $qty is only used as a legacy fallback inside restoreStock() when no drain-ledger
        // rows exist for this withdrawal (i.e. it was completed before this feature shipped).
        $qty = (float) $withdrawal->quantity;

        $this->restoreStock($withdrawal->inventory_id, $qty, 'withdrawal', $withdrawal->id);

        $withdrawal->update(['status_id' => $cancelledId]);

        return [
            'data'    => new InventoryWithdrawalResource($withdrawal->fresh()->load(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'Withdrawal voided and stock restored.',
            'info'    => null,
        ];
    }

    public function partialIssueWithdrawal(Request $request, InventoryWithdrawal $withdrawal): array
    {
        $issuedQty = (float) $request->input('issued_quantity', 0);
        if ($issuedQty < 0.01) {
            throw new \Exception('Issued quantity must be at least 0.01.');
        }

        $completedId    = (int) ListStatus::getID('Completed', 'Inventory');

        if ((int) $withdrawal->status_id === $completedId) {
            throw new \Exception('This withdrawal is already completed.');
        }
        $totalRequested = (float) $withdrawal->quantity;
        $alreadyIssued  = (float) ($withdrawal->issued_quantity ?? 0);
        $newIssued      = $alreadyIssued + $issuedQty;

        $value = $this->drainStock($withdrawal->inventory_id, $issuedQty, 'withdrawal', $withdrawal->id);

        // Running weighted-average unit cost across every partial issuance for this withdrawal.
        $priorValue  = (float) ($withdrawal->unit_cost ?? 0) * $alreadyIssued;
        $newUnitCost = $newIssued > 0 ? round(($priorValue + $value) / $newIssued, 4) : null;

        $newStatusId = $newIssued >= $totalRequested ? $completedId : $withdrawal->status_id;

        $withdrawal->update([
            'issued_quantity' => $newIssued,
            'unit_cost'       => $newUnitCost,
            'status_id'       => $newStatusId,
            'remarks'         => $request->input('remarks', $withdrawal->remarks),
        ]);

        return [
            'data'    => new InventoryWithdrawalResource($withdrawal->fresh()->load(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => 'Partial issuance recorded.',
            'info'    => "Issued {$issuedQty} of {$totalRequested} units.",
        ];
    }

    protected function postPhysicalCountAdjustments(InventoryPhysicalCount $count): void
    {
        foreach ($count->items()->where(DB::raw('ABS(physical_quantity - system_quantity)'), '>', 0)->get() as $line) {
            $stock = InventoryStock::where('item_id', $line->item_id)->orderByDesc('quantity')->lockForUpdate()->first();
            if (! $stock) {
                continue;
            }

            $physical = (float) $line->physical_quantity;
            $system   = (float) $line->system_quantity;

            InventoryStockAdjustment::create([
                'item_id'           => $line->item_id,
                'stock_id'          => $stock->id,
                'type'              => 'correction',
                'quantity_before'   => $system,
                'quantity_adjusted' => $physical,
                'quantity_after'    => $physical,
                'reason'            => "Physical count #{$count->count_no}",
                'adjusted_by_id'    => $count->counted_by_id,
                'approved_by_id'    => $count->approved_by_id,
                'status_id'         => $count->status_id,
                'adjustment_date'   => $count->count_date,
            ]);

            $stock->quantity = $physical;
            $stock->save();
        }
    }

    /**
     * Build the running-balance stock-card ledger for one item: every receiving,
     * withdrawal, and adjustment sorted by date, with a running quantity balance and
     * a weighted-average unit cost carried forward (unit cost only changes on "in"
     * transactions, per standard weighted-average costing — an "out" doesn't change
     * the average cost of what remains).
     */
    public function stockCardLedger(InventoryItem $item): \Illuminate\Support\Collection
    {
        $receivings  = $item->receivings()->with('status')->orderBy('received_at')->get();
        $withdrawals = $item->withdrawals()->with('status')->orderBy('released_at')->get();
        $adjustments = InventoryStockAdjustment::where('item_id', $item->id)->with('stock')->orderBy('adjustment_date')->get();

        $rows = collect();

        foreach ($receivings as $r) {
            $rows->push(['date' => $r->received_at, 'sort' => 0, 'type' => 'Receiving', 'ref' => null, 'in' => (float) $r->quantity, 'out' => 0, 'unit_cost' => $r->unit_cost !== null ? (float) $r->unit_cost : null, 'remarks' => $r->remarks]);
        }
        foreach ($withdrawals as $w) {
            $rows->push(['date' => $w->released_at, 'sort' => 1, 'type' => 'Withdrawal', 'ref' => null, 'in' => 0, 'out' => (float) $w->quantity, 'unit_cost' => $w->unit_cost !== null ? (float) $w->unit_cost : null, 'remarks' => $w->remarks]);
        }
        foreach ($adjustments as $a) {
            $in  = in_array($a->type, ['increase', 'correction'], true) ? (float) $a->quantity_adjusted : 0;
            $out = $a->type === 'decrease' ? (float) $a->quantity_adjusted : 0;
            $rows->push(['date' => $a->adjustment_date, 'sort' => 2, 'type' => 'Adjustment', 'ref' => $a->adjustment_no, 'in' => $in, 'out' => $out, 'unit_cost' => $a->stock?->unit_cost !== null ? (float) $a->stock->unit_cost : null, 'remarks' => $a->reason]);
        }

        $rows = $rows->sortBy([['date', 'asc'], ['sort', 'asc']])->values();

        $balance = 0.0;
        $avgCost = 0.0;

        $ledger = collect();

        foreach ($rows as $row) {
            $in  = $row['in'];
            $out = $row['out'];

            if ($in > 0) {
                $incomingCost = $row['unit_cost'] ?? $avgCost;
                $avgCost = ($balance + $in) > 0
                    ? (($balance * $avgCost) + ($in * $incomingCost)) / ($balance + $in)
                    : $incomingCost;
                $balance += $in;
            } elseif ($out > 0) {
                $balance -= $out;
            }

            $ledger->push([
                'date'          => $row['date'],
                'type'          => $row['type'],
                'ref'           => $row['ref'],
                'in'            => $in,
                'out'           => $out,
                'unit_cost'     => $row['unit_cost'],
                'balance'       => $balance,
                'balance_cost'  => $avgCost,
                'remarks'       => $row['remarks'],
            ]);
        }

        return $ledger;
    }

    public function procurementReceivings(Request $request)
    {
        $query = ProcurementNoaPo::with([
                'iars:id,po_id,code',
                'inventoryTransfers.inventoryItem:id,code,name',
                'inventoryTransfers.inventoryStock:id,item_id,quantity,unit_id,unit_cost',
                'inventoryTransfers.inventoryStock.unit:id,name_short',
            ])
            ->select('id', 'code', 'po_date')
            ->whereHas('inventoryTransfers')
            ->orderByDesc('po_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('inventoryTransfers.inventoryItem', fn ($inner) =>
                      $inner->where('name', 'like', "%{$search}%")
                  );
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $query->whereHas('inventoryTransfers.inventoryStock', function ($q) use ($status) {
                if ($status === 'depleted') {
                    $q->where('quantity', '<=', 0);
                } elseif ($status === 'low') {
                    $q->where('quantity', '>', 0)->where('quantity', '<', 5);
                } elseif ($status === 'in_stock') {
                    $q->where('quantity', '>=', 5);
                }
            });
        }

        return $query->paginate(10);
    }

    public function itemProperties(Request $request)
    {
        return InventoryItemPropertyResource::collection(
            InventoryItemProperty::with('item:id,code,name')
                ->when($request->filled('item_id'), fn ($q) => $q->where('inventory_item_id', $request->integer('item_id')))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
                ->when($request->filled('keyword'), function ($q) use ($request) {
                    $kw = '%'.trim($request->input('keyword')).'%';
                    $q->where(function ($inner) use ($kw) {
                        $inner->where('property_code', 'like', $kw)
                            ->orWhere('model', 'like', $kw)
                            ->orWhere('serial_no', 'like', $kw)
                            ->orWhereHas('item', fn ($item) => $item->where('name', 'like', $kw)->orWhere('code', 'like', $kw));
                    });
                })
                ->orderByDesc('id')
                ->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    public function saveItemProperty(Request $request): array
    {
        $property = InventoryItemProperty::create($request->validated());

        return $this->itemPropertyResult(
            $property,
            'Property record created successfully.',
            "You've successfully added a property record."
        );
    }

    public function updateItemProperty(Request $request, InventoryItemProperty $property): array
    {
        $property->update($request->validated());

        return $this->itemPropertyResult(
            $property,
            'Property record updated successfully.',
            "You've successfully updated the property record."
        );
    }

    public function deleteItemProperty(InventoryItemProperty $property): array
    {
        $id = $property->id;
        $property->delete();

        return $this->deleteResult($id, 'Property record deleted successfully.', "You've successfully deleted the property record.");
    }

    protected function itemPropertyResult(InventoryItemProperty $property, string $message, string $info): array
    {
        return [
            'data'    => new InventoryItemPropertyResource($property->load('item')),
            'message' => $message,
            'info'    => $info,
        ];
    }

    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return ListDropdown::where('classification', 'Item Category')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function saveCategory(Request $request): array
    {
        $category = ListDropdown::create([
            'name'           => $request->input('name'),
            'classification' => 'Item Category',
            'type'           => 'n/a',
            'color'          => 'n/a',
            'others'         => 'n/a',
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return [
            'data'    => $category,
            'message' => 'Category created.',
            'info'    => null,
        ];
    }

    public function updateCategory(Request $request, int $id): array
    {
        $category = ListDropdown::where('classification', 'Item Category')->findOrFail($id);

        $category->update([
            'name'      => $request->input('name'),
            'is_active' => $request->boolean('is_active', (bool) $category->is_active),
        ]);

        return [
            'data'    => $category->fresh(),
            'message' => 'Category updated.',
            'info'    => null,
        ];
    }

    public function deleteCategory(int $id): array
    {
        $category = ListDropdown::where('classification', 'Item Category')->findOrFail($id);

        if (InventoryItem::where('category_id', $id)->exists()) {
            throw new \Exception('Cannot delete a category that still has items assigned to it.');
        }

        $category->delete();

        return [
            'data'    => ['id' => $id],
            'message' => 'Category deleted.',
            'info'    => null,
        ];
    }

    public function reports()
    {
        return InventoryReportResource::collection(
            InventoryReport::with(['title:id,name,data_source,category_id', 'category:id,name', 'creator.profile'])
                ->orderByDesc('id')
                ->get()
        );
    }

    public function showReport(int $id): array
    {
        $report = InventoryReport::with(['title:id,name,data_source,category_id', 'category:id,name', 'creator.profile'])
            ->findOrFail($id);

        return [
            'report' => new InventoryReportResource($report),
            'rows'   => $this->reportDetailRows($report),
        ];
    }

    public function reportDetailRows(InventoryReport $report): array
    {
        $source = $report->title?->data_source ?? InventoryReportTitle::SOURCE_NONE;
        $start  = $report->period_start;
        $end    = $report->period_end;

        if (in_array($source, [InventoryReportTitle::SOURCE_ITEMS_RECEIVED, InventoryReportTitle::SOURCE_STOCKS_RECEIVED], true)) {
            $categoryTotals = [];
            $grandTotal = 0.0;

            $rows = InventoryReceiving::with(['item.category', 'stock:id,description'])
                ->when($start, fn ($q) => $q->whereDate('received_at', '>=', $start))
                ->when($end, fn ($q) => $q->whereDate('received_at', '<=', $end))
                ->orderBy('received_at')
                ->get()
                ->map(function ($r) use (&$categoryTotals, &$grandTotal) {
                    $totalCost = (float) $r->quantity * (float) $r->unit_cost;
                    $grandTotal += $totalCost;
                    $categoryName = $r->item?->category?->name ?? 'Uncategorized';
                    $categoryTotals[$categoryName] = ($categoryTotals[$categoryName] ?? 0) + $totalCost;

                    return [
                        'id'          => $r->id,
                        'code'        => $r->item?->code,
                        'name'        => $r->item?->name,
                        'stock'       => $r->stock?->description,
                        'quantity'    => (float) $r->quantity,
                        'unit_cost'   => (float) $r->unit_cost,
                        'total_cost'  => $totalCost,
                        'date'        => optional($r->received_at)->format('Y-m-d'),
                        'status'      => $r->status?->name,
                    ];
                })
                ->values();

            return array_merge(
                ['kind' => 'received', 'columns' => $this->reportColumns('received'), 'rows' => $rows,
                    'grand_total' => $grandTotal, 'category_totals' => $categoryTotals],
                $this->reportSignatories()
            );
        }

        if (in_array($source, [InventoryReportTitle::SOURCE_ITEMS_WITHDRAWN, InventoryReportTitle::SOURCE_STOCKS_WITHDRAWN], true)) {
            $categoryTotals = [];
            $grandTotal = 0.0;

            $rows = InventoryWithdrawal::with(['item.category'])
                ->when($start, fn ($q) => $q->whereDate('released_at', '>=', $start))
                ->when($end, fn ($q) => $q->whereDate('released_at', '<=', $end))
                ->orderBy('released_at')
                ->get()
                ->map(function ($w) use (&$categoryTotals, &$grandTotal) {
                    $totalCost = (float) $w->issued_quantity * (float) $w->unit_cost;
                    $grandTotal += $totalCost;
                    $categoryName = $w->item?->category?->name ?? 'Uncategorized';
                    $categoryTotals[$categoryName] = ($categoryTotals[$categoryName] ?? 0) + $totalCost;

                    return [
                        'id'               => $w->id,
                        'code'             => $w->item?->code,
                        'name'             => $w->item?->name,
                        'quantity'         => (float) $w->quantity,
                        'issued_quantity'  => (float) $w->issued_quantity,
                        'unit_cost'        => (float) $w->unit_cost,
                        'total_cost'       => $totalCost,
                        'date'             => optional($w->released_at)->format('Y-m-d'),
                        'status'           => $w->status?->name,
                    ];
                })
                ->values();

            return array_merge(
                ['kind' => 'withdrawn', 'columns' => $this->reportColumns('withdrawn'), 'rows' => $rows,
                    'grand_total' => $grandTotal, 'category_totals' => $categoryTotals],
                $this->reportSignatories()
            );
        }

        if ($source === InventoryReportTitle::SOURCE_RIS_ISSUED) {
            return $this->reportRisIssuedData($start, $end);
        }

        return ['kind' => 'none', 'columns' => [], 'rows' => collect(), 'grand_total' => 0, 'category_totals' => []];
    }

    protected function reportColumns(string $kind): array
    {
        return $kind === 'received'
            ? ['Code', 'Item', 'Stock', 'Quantity', 'Unit Cost', 'Total Cost', 'Date Received', 'Status']
            : ['Code', 'Item', 'Qty Requested', 'Qty Issued', 'Unit Cost', 'Total Cost', 'Date Released', 'Status'];
    }

    /**
     * Report of Supplies and Materials Issued (RSMI)-style data: RIS-grouped issued
     * items with their FIFO drain unit cost, a per-category subtotal, and the
     * Supply Officer / Chief Accountant signatories.
     */
    public function reportRisIssuedData($start, $end): array
    {
        $ris = InventoryRis::with(['items.item.category'])
            ->when($start, fn ($q) => $q->whereDate('ris_date', '>=', $start))
            ->when($end, fn ($q) => $q->whereDate('ris_date', '<=', $end))
            ->orderBy('ris_no')
            ->get();

        $grandTotal = 0.0;
        $categoryTotals = [];

        $groups = $ris->map(function ($r) use (&$grandTotal, &$categoryTotals) {
            $items = $r->items
                ->filter(fn ($risItem) => (float) $risItem->quantity_issued > 0)
                ->map(function ($risItem) use (&$grandTotal, &$categoryTotals) {
                    $drains = InventoryStockDrain::where('source_type', 'ris_item')
                        ->where('source_id', $risItem->id)
                        ->get();

                    $drainedQty = (float) $drains->sum('quantity');
                    $drainedValue = (float) $drains->sum(fn ($d) => (float) $d->quantity * (float) $d->unit_cost);
                    $unitCost = $drainedQty > 0 ? $drainedValue / $drainedQty : 0.0;
                    $quantity = (float) $risItem->quantity_issued;
                    $amount = $quantity * $unitCost;

                    $grandTotal += $amount;
                    $categoryName = $risItem->item?->category?->name ?? 'Uncategorized';
                    $categoryTotals[$categoryName] = ($categoryTotals[$categoryName] ?? 0) + $amount;

                    return [
                        'item_no'   => $risItem->item?->code,
                        'item_name' => $risItem->item?->name,
                        'unit'      => $risItem->unit_of_issue,
                        'quantity'  => $quantity,
                        'unit_cost' => $unitCost,
                        'amount'    => $amount,
                    ];
                })
                ->values();

            return [
                'ris_no'                => $r->ris_no,
                'responsibility_center' => $r->responsibility_center,
                'items'                 => $items,
            ];
        })->filter(fn ($group) => $group['items']->isNotEmpty())->values();

        return array_merge([
            'kind'    => 'ris_issued',
            'columns' => [],
            'rows'    => collect(),
            'groups'          => $groups,
            'grand_total'     => $grandTotal,
            'category_totals' => $categoryTotals,
        ], $this->reportSignatories());
    }

    /**
     * Supply Officer / Chief Accountant signatories shown on every printed/viewed
     * inventory report, regardless of which dataset the report pulls from.
     */
    protected function reportSignatories(): array
    {
        $supplyOfficer = User::whereHasActiveRole('Supply Officer')->with('profile')->first();
        $accountantOrgChart = OrgChart::where('designation_id', ListDropdown::getID('Chief Accountant', 'Designation'))
            ->with('user.profile')
            ->first();

        return [
            'supply_officer' => $supplyOfficer ? [
                'name' => strtoupper($supplyOfficer->profile?->fullname ?? $supplyOfficer->username),
                'role' => 'Supply Officer',
            ] : null,
            'accountant' => $accountantOrgChart?->user ? [
                'name' => strtoupper($accountantOrgChart->user->profile?->fullname ?? $accountantOrgChart->user->username),
                'role' => 'Accountant III',
            ] : null,
        ];
    }

    public function saveReport(Request $request): array
    {
        $report = InventoryReport::create([
            'title_id'       => $request->input('title_id'),
            'category_id'    => $request->input('category_id'),
            'period_type'    => $request->input('period_type'),
            'period_year'    => $request->input('period_year'),
            'period_month'   => $request->input('period_month'),
            'period_quarter' => $request->input('period_quarter'),
            'period_start'   => $request->input('period_start'),
            'period_end'     => $request->input('period_end'),
            'period_label'   => $request->input('period_label'),
            'created_by_id'  => Auth::id(),
        ]);

        return [
            'data'    => new InventoryReportResource($report->load(['title:id,name,data_source,category_id', 'category:id,name', 'creator.profile'])),
            'message' => 'Report created successfully!',
            'info'    => "{$report->code} was added.",
            'status'  => true,
        ];
    }

    public function updateReport(Request $request, int $id): array
    {
        $report = InventoryReport::findOrFail($id);

        $report->update([
            'title_id'       => $request->input('title_id'),
            'category_id'    => $request->input('category_id'),
            'period_type'    => $request->input('period_type'),
            'period_year'    => $request->input('period_year'),
            'period_month'   => $request->input('period_month'),
            'period_quarter' => $request->input('period_quarter'),
            'period_start'   => $request->input('period_start'),
            'period_end'     => $request->input('period_end'),
            'period_label'   => $request->input('period_label'),
        ]);

        return [
            'data'    => new InventoryReportResource($report->fresh(['title:id,name,data_source,category_id', 'category:id,name', 'creator.profile'])),
            'message' => 'Report updated successfully!',
            'info'    => "{$report->code} was updated.",
            'status'  => true,
        ];
    }

    public function deleteReport(int $id): array
    {
        $report = InventoryReport::findOrFail($id);
        $code = $report->code;
        $report->delete();

        return [
            'data'    => ['id' => $id],
            'message' => 'Report deleted successfully!',
            'info'    => "{$code} was removed.",
            'status'  => true,
        ];
    }

    public function stockAdjustments(Request $request)
    {
        return InventoryStockAdjustment::with(['item:id,code,name', 'adjustedBy.profile', 'approvedBy.profile', 'status'])
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $kw = '%' . trim($request->input('keyword')) . '%';
                $q->where(function ($inner) use ($kw) {
                    $inner->where('adjustment_no', 'like', $kw)
                          ->orWhere('reason', 'like', $kw)
                          ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $kw)->orWhere('code', 'like', $kw));
                });
            })
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id')
            ->paginate(max((int) $request->input('count', 10), 1));
    }

    public function saveStockAdjustment(Request $request): array
    {
        $data = $request->validated();
        $data['adjusted_by_id'] = $data['adjusted_by_id'] ?? Auth::id();

        $stock = isset($data['stock_id'])
            ? InventoryStock::find($data['stock_id'])
            : InventoryStock::where('item_id', $data['item_id'])->orderByDesc('quantity')->lockForUpdate()->first();

        $before = $stock ? (float) $stock->quantity : 0;
        $qty    = (float) $data['quantity_adjusted'];

        $data['quantity_before'] = $before;
        $data['quantity_after']  = $this->computeAdjustedQuantity($data['type'], $before, $qty);

        $adjustment = InventoryStockAdjustment::create($data);

        $approvedId = (int) ListStatus::getID('Approved', 'Inventory');
        if ($approvedId && (int) $data['status_id'] === $approvedId) {
            $this->applyStockAdjustment($adjustment, $stock);
        }

        return [
            'data'    => $adjustment->load(['item', 'adjustedBy.profile', 'approvedBy.profile', 'status']),
            'message' => 'Stock adjustment recorded successfully.',
            'info'    => null,
        ];
    }

    public function updateStockAdjustment(Request $request, InventoryStockAdjustment $adjustment): array
    {
        $oldStatusId = (int) $adjustment->status_id;
        $newStatusId = (int) $request->input('status_id');
        $approvedId  = (int) ListStatus::getID('Approved', 'Inventory');

        $data = $request->validated();

        if ($oldStatusId !== $approvedId) {
            $stock = isset($data['stock_id'])
                ? InventoryStock::find($data['stock_id'])
                : InventoryStock::where('item_id', $data['item_id'])->orderByDesc('quantity')->lockForUpdate()->first();

            $before = $stock ? (float) $stock->quantity : 0;
            $qty    = (float) $data['quantity_adjusted'];

            $data['quantity_before'] = $before;
            $data['quantity_after']  = $this->computeAdjustedQuantity($data['type'], $before, $qty);
        }

        $adjustment->update($data);

        if ($approvedId && $newStatusId === $approvedId && $oldStatusId !== $approvedId) {
            $stock = $adjustment->stock
                ?? InventoryStock::where('item_id', $adjustment->item_id)->orderByDesc('quantity')->first();
            $this->applyStockAdjustment($adjustment->fresh(), $stock);
        }

        return [
            'data'    => $adjustment->fresh()->load(['item', 'adjustedBy.profile', 'approvedBy.profile', 'status']),
            'message' => 'Stock adjustment updated.',
            'info'    => null,
        ];
    }

    public function deleteStockAdjustment(InventoryStockAdjustment $adjustment): array
    {
        $approvedId = (int) ListStatus::getID('Approved', 'Inventory');
        if ((int) $adjustment->status_id === $approvedId) {
            throw new \Exception('Approved adjustments cannot be deleted.');
        }

        $id = $adjustment->id;
        $adjustment->delete();

        return [
            'data'    => ['id' => $id],
            'message' => 'Adjustment deleted.',
            'info'    => null,
        ];
    }

    /**
     * @throws \Exception if a "decrease" would take more than is currently on hand —
     *     silently clamping to 0 would misrepresent quantity_adjusted vs what actually happened.
     */
    private function computeAdjustedQuantity(string $type, float $before, float $qty): float
    {
        return match ($type) {
            'increase' => $before + $qty,
            'decrease' => $qty > $before
                ? throw new \Exception("Cannot decrease by {$qty}: only {$before} on hand.")
                : $before - $qty,
            'correction' => $qty,
        };
    }

    private function applyStockAdjustment(InventoryStockAdjustment $adj, ?InventoryStock $stock): void
    {
        if (! $stock) {
            InventoryStock::create([
                'item_id'  => $adj->item_id,
                'quantity' => $adj->quantity_after,
                'unit_id'  => \App\Models\UnitType::orderBy('id')->value('id') ?? 1,
            ]);
            return;
        }

        $stock->quantity = $adj->quantity_after;
        $stock->save();
    }

    protected function stocksQuery(Request $request)
    {
        return InventoryStock::query()
            ->with(['item:id,code,name', 'unit:id,name_short,name_long'])
            ->when($request->filled('item_id'), function ($query) use ($request) {
                $query->where('item_id', $request->integer('item_id'));
            })
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));
                $query->whereHas('item', function ($inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                          ->orWhere('code', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('id');
    }

    protected function itemsQuery(Request $request)
    {
        return $this->applyItemSorting(
            InventoryItem::with(['category:id,name'])
                ->withCount('stocks')
                ->withSum('stocks', 'quantity')
                ->addSelect(DB::raw(
                    '(SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM inventory_stocks WHERE item_id = inventory_items.id) as total_value'
                ))
                ->when($request->filled('category_id'), function ($query) use ($request) {
                    $query->where('category_id', $request->integer('category_id'));
                })
                ->when($request->filled('keyword'), function ($query) use ($request) {
                    $keyword = trim((string) $request->input('keyword'));

                    $query->where(function ($inner) use ($keyword) {
                        $inner->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhereHas('category', function ($category) use ($keyword) {
                                $category->where('name', 'like', "%{$keyword}%");
                            });
                    });
                }),
            (string) $request->input('sort', 'latest')
        );
    }

    protected function receivingsQuery(Request $request)
    {
        return InventoryReceiving::with(['item', 'approvedBy.profile', 'status'])
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
            ->orderByDesc('id');
    }

    protected function withdrawalsQuery(Request $request)
    {
        return InventoryWithdrawal::with(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])
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
            ->orderByDesc('id');
    }

    protected function inventoryStatuses()
    {
        return ListStatus::query()
            ->where('is_active', 1)
            ->where('classification', 'Inventory')
            ->whereIn('name', ['Pending', 'Approved', 'Completed', 'Cancelled', 'Disapproved'])
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    protected function applyItemSorting($query, string $sort)
    {
        return match ($sort) {
            'oldest'       => $query->orderBy('id'),
            'name_asc'     => $query->orderBy('name')->orderByDesc('id'),
            'name_desc'    => $query->orderByDesc('name')->orderByDesc('id'),
            'quantity_desc' => $query->orderByDesc('stocks_sum_quantity')->orderByDesc('id'),
            'quantity_asc'  => $query->orderBy('stocks_sum_quantity')->orderByDesc('id'),
            default        => $query->orderByDesc('id'),
        };
    }

    protected function perPage(Request $request): int
    {
        return max((int) $request->input('count', 10), 1);
    }

    protected function stockResult(InventoryStock $stock, string $message, string $info): array
    {
        return [
            'data' => new InventoryStockResource($stock->load(['item', 'unit'])),
            'message' => $message,
            'info' => $info,
        ];
    }

    protected function itemResult(InventoryItem $item, string $message, string $info): array
    {
        return [
            'data' => new InventoryItemResource($item->load(['category'])),
            'message' => $message,
            'info' => $info,
        ];
    }

    protected function receivingResult(InventoryReceiving $receiving, string $message, string $info): array
    {
        return [
            'data' => new InventoryReceivingResource($receiving->load(['item', 'approvedBy.profile', 'status'])),
            'message' => $message,
            'info' => $info,
        ];
    }

    protected function withdrawalResult(InventoryWithdrawal $withdrawal, string $message, string $info): array
    {
        return [
            'data' => new InventoryWithdrawalResource($withdrawal->load(['item', 'requestedBy.profile', 'approvedBy.profile', 'status'])),
            'message' => $message,
            'info' => $info,
        ];
    }

    protected function deleteResult(int $id, string $message, string $info): array
    {
        return [
            'data' => ['id' => $id],
            'message' => $message,
            'info' => $info,
        ];
    }
}
