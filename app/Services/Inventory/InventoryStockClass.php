<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryIcsResource;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryParResource;
use App\Http\Resources\Inventory\InventoryPhysicalCountResource;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Http\Resources\Inventory\InventoryRisResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
use App\Models\InventoryIcs;
use App\Models\InventoryIcsItem;
use App\Models\InventoryItem;
use App\Models\InventoryPar;
use App\Models\InventoryParItem;
use App\Models\InventoryPhysicalCount;
use App\Models\InventoryPhysicalCountItem;
use App\Models\InventoryRis;
use App\Models\InventoryRisItem;
use App\Models\InventoryReceiving;
use App\Models\InventoryStock;
use App\Models\InventoryStockAdjustment;
use App\Models\InventoryWithdrawal;
use App\Models\ListDropdown;
use App\Models\ListStatus;
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
            ],
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
        $receiving = InventoryReceiving::create($request->validated());

        return $this->receivingResult(
            $receiving,
            'Inventory receiving created successfully.',
            "You've successfully logged a receiving record."
        );
    }

    public function updateReceiving($request, InventoryReceiving $receiving): array
    {
        $receiving->update($request->validated());

        return $this->receivingResult(
            $receiving,
            'Inventory receiving updated successfully.',
            "You've successfully updated the receiving record."
        );
    }

    public function deleteReceiving(InventoryReceiving $receiving): array
    {
        $id = $receiving->id;
        $receiving->delete();

        return $this->deleteResult($id, 'Inventory receiving deleted successfully.', "You've successfully deleted the receiving record.");
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

            $stock = InventoryStock::where('item_id', $risItem->item_id)
                ->orderByDesc('quantity')
                ->first();

            if ($stock) {
                $stock->quantity = (float) $stock->quantity + $issued;
                $stock->save();
            }
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

            $this->drainStock($risItem->item_id, $remaining);
        }
    }

    public function deductStockForWithdrawal(InventoryWithdrawal $withdrawal): void
    {
        $qty = (float) $withdrawal->quantity;
        if ($qty <= 0) {
            return;
        }

        $this->drainStock($withdrawal->inventory_id, $qty);
    }

    public function drainStock(int $itemId, float $needed): void
    {
        $stocks = InventoryStock::where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->get();

        foreach ($stocks as $stock) {
            if ($needed <= 0) {
                break;
            }

            $available = (float) $stock->quantity;
            $deduct    = min($available, $needed);
            $stock->quantity = $available - $deduct;
            $stock->save();
            $needed -= $deduct;
        }
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

        $qty = (float) $withdrawal->quantity;

        $stock = InventoryStock::where('item_id', $withdrawal->inventory_id)
            ->orderByDesc('quantity')
            ->first();

        if ($stock && $qty > 0) {
            $stock->quantity = (float) $stock->quantity + $qty;
            $stock->save();
        }

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

        $this->drainStock($withdrawal->inventory_id, $issuedQty);

        $newStatusId = $newIssued >= $totalRequested ? $completedId : $withdrawal->status_id;

        $withdrawal->update([
            'issued_quantity' => $newIssued,
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
            $stock = InventoryStock::where('item_id', $line->item_id)->orderByDesc('quantity')->first();
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
        $category->delete();

        return [
            'data'    => ['id' => $id],
            'message' => 'Category deleted.',
            'info'    => null,
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
            : InventoryStock::where('item_id', $data['item_id'])->orderByDesc('quantity')->first();

        $before = $stock ? (float) $stock->quantity : 0;
        $qty    = (float) $data['quantity_adjusted'];

        $data['quantity_before'] = $before;
        $data['quantity_after']  = match ($data['type']) {
            'increase'   => $before + $qty,
            'decrease'   => max($before - $qty, 0),
            'correction' => $qty,
        };

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
                : InventoryStock::where('item_id', $data['item_id'])->orderByDesc('quantity')->first();

            $before = $stock ? (float) $stock->quantity : 0;
            $qty    = (float) $data['quantity_adjusted'];

            $data['quantity_before'] = $before;
            $data['quantity_after']  = match ($data['type']) {
                'increase'   => $before + $qty,
                'decrease'   => max($before - $qty, 0),
                'correction' => $qty,
            };
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
