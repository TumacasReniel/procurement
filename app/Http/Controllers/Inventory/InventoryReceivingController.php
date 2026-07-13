<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\RespondsWithInventoryResults;
use App\Http\Requests\Inventory\InventoryReceivingRequest;
use App\Models\InventoryReceiving;
use App\Models\InventoryReceivingTransfer;
use App\Models\ProcurementNoaPo;
use App\Services\Inventory\InventoryStockClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class InventoryReceivingController extends Controller
{
    use HandlesTransaction;
    use RespondsWithInventoryResults;

    public function __construct(public InventoryStockClass $inventory)
    {
    }

    public function index(Request $request)
    {
        if (!$this->shouldReturnJson($request)) {
            return redirect('/inventory-stocks?tab=receivings');
        }

        return $this->inventory->receivings($request);
    }

    public function store(InventoryReceivingRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->inventory->saveReceiving($request);
        });

        return $this->inventoryResultResponse($request, $result, 'receivings');
    }

    public function update(InventoryReceivingRequest $request, InventoryReceiving $inventory_receiving)
    {
        $result = $this->handleTransaction(function () use ($request, $inventory_receiving) {
            return $this->inventory->updateReceiving($request, $inventory_receiving);
        });

        return $this->inventoryResultResponse($request, $result, 'receivings');
    }

    public function destroy(Request $request, InventoryReceiving $inventory_receiving)
    {
        $result = $this->handleTransaction(function () use ($inventory_receiving) {
            return $this->inventory->deleteReceiving($inventory_receiving);
        });

        return $this->inventoryResultResponse($request, $result, 'receivings');
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
}
