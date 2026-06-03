<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
use App\Models\InventoryItem;
use App\Models\InventoryReceiving;
use App\Models\InventoryStock;
use App\Models\InventoryWithdrawal;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Http\Request;

class InventoryStockClass
{
    public function indexData(Request $request): array
    {
        return [
            'dropdowns' => [
                'categories' => ListDropdown::where('classification', 'Inventory Category')
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
        $withdrawal->update($request->validated());

        return $this->withdrawalResult(
            $withdrawal,
            'Inventory withdrawal updated successfully.',
            "You've successfully updated the withdrawal record."
        );
    }

    public function deleteWithdrawal(InventoryWithdrawal $withdrawal): array
    {
        $id = $withdrawal->id;
        $withdrawal->delete();

        return $this->deleteResult($id, 'Inventory withdrawal deleted successfully.', "You've successfully deleted the withdrawal record.");
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
            ->whereIn('name', ['Pending', 'Approved', 'Completed', 'Cancelled', 'Disapproved'])
            ->orderBy('id')
            ->get(['id', 'name'])
            ->unique('name')
            ->values();
    }

    protected function applyItemSorting($query, string $sort)
    {
        return match ($sort) {
            'oldest' => $query->orderBy('id'),
            'name_asc' => $query->orderBy('name')->orderByDesc('id'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('id'),
            'quantity_desc' => $query->orderByDesc('quantity')->orderByDesc('id'),
            'quantity_asc' => $query->orderBy('quantity')->orderByDesc('id'),
            'expiration_asc' => $query->orderBy('expiration')->orderByDesc('id'),
            'expiration_desc' => $query->orderByDesc('expiration')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
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
