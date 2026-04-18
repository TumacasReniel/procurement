<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryStockRequest;
use App\Http\Resources\Inventory\InventoryReceivingResource;
use App\Http\Resources\Inventory\InventoryStockResource;
use App\Http\Resources\Inventory\InventoryWithdrawalResource;
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
        if ($this->shouldReturnJson($request)) {
            return $this->stocksCollection($request);
        }

        return Inertia::render('Modules/Inventory/Index', [
            'initialTab' => $this->resolveTab((string) $request->query('tab', 'stocks')),
            'dropdowns' => [
                'categories' => ListDropdown::where('classification', 'Inventory Category')
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'statuses' => $this->inventoryStatuses(),
            ],
            'users' => User::with('profile')
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->profile?->fullname ?? $user->username,
                ])
                ->sortBy('name')
                ->values(),
            'stockOptions' => InventoryStock::orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn ($stock) => [
                    'id' => $stock->id,
                    'code' => $stock->code,
                    'name' => $stock->name,
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
            'stocks' => $this->stocksCollection($request),
            'items' => $this->itemsCollection($request),
            'receivings' => $this->receivingsCollection($request),
            'withdrawals' => $this->withdrawalsCollection($request),
        ]);
    }

    public function store(InventoryStockRequest $request)
    {
        $stock = InventoryStock::create($request->validated());
        $stock->loadCount('items');
        $stock->setAttribute('total_quantity', (int) $stock->items()->sum('quantity'));

        return response()->json([
            'data' => new InventoryStockResource($stock),
            'message' => 'Inventory stock created successfully.',
        ]);
    }

    public function update(InventoryStockRequest $request, InventoryStock $inventory_stock)
    {
        $inventory_stock->update($request->validated());
        $inventory_stock->loadCount('items');
        $inventory_stock->setAttribute('total_quantity', (int) $inventory_stock->items()->sum('quantity'));

        return response()->json([
            'data' => new InventoryStockResource($inventory_stock),
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

    protected function shouldReturnJson(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->wantsJson() || $request->ajax() || $request->boolean('json');
    }

    protected function resolveTab(string $tab): string
    {
        return in_array($tab, ['stocks', 'items', 'receivings', 'withdrawals'], true)
            ? $tab
            : 'stocks';
    }

    protected function stocksCollection(Request $request)
    {
        return InventoryStockResource::collection(
            $this->stocksQuery($request)->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    protected function itemsCollection(Request $request)
    {
        return \App\Http\Resources\Inventory\InventoryItemResource::collection(
            $this->itemsQuery($request)->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    protected function receivingsCollection(Request $request)
    {
        return InventoryReceivingResource::collection(
            $this->receivingsQuery($request)->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    protected function withdrawalsCollection(Request $request)
    {
        return InventoryWithdrawalResource::collection(
            $this->withdrawalsQuery($request)->paginate(max((int) $request->input('count', 10), 1))
        );
    }

    protected function stocksQuery(Request $request)
    {
        return InventoryStock::query()
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('entry_date')
            ->orderByDesc('id');
    }

    protected function itemsQuery(Request $request)
    {
        return InventoryItem::with(['stock:id,code,name', 'category:id,name'])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhereHas('stock', function ($stock) use ($keyword) {
                            $stock->where('code', 'like', "%{$keyword}%")
                                ->orWhere('name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('category', function ($category) use ($keyword) {
                            $category->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderByDesc('id');
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
}
