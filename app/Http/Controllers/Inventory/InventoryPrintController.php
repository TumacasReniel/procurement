<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryIcs;
use App\Models\InventoryItem;
use App\Models\InventoryPar;
use App\Models\InventoryRis;
use App\Models\InventoryStockAdjustment;
use Illuminate\Http\Request;

class InventoryPrintController extends Controller
{
    public function stockCard(InventoryItem $item)
    {
        $item->load(['category', 'stocks.unit']);

        $receivings  = $item->receivings()->with('status')->orderBy('received_at')->get();
        $withdrawals = $item->withdrawals()->with('status')->orderBy('released_at')->get();
        $adjustments = InventoryStockAdjustment::where('item_id', $item->id)->orderBy('adjustment_date')->get();

        // Build running balance ledger
        $ledger  = collect();
        $balance = 0;

        foreach ($receivings as $r) {
            $qty     = (float) $r->quantity;
            $balance += $qty;
            $ledger->push(['date' => $r->received_at, 'type' => 'Receiving', 'ref' => null, 'in' => $qty, 'out' => 0, 'balance' => $balance, 'remarks' => $r->remarks]);
        }
        foreach ($withdrawals as $w) {
            $qty     = (float) $w->quantity;
            $balance -= $qty;
            $ledger->push(['date' => $w->released_at, 'type' => 'Withdrawal', 'ref' => null, 'in' => 0, 'out' => $qty, 'balance' => $balance, 'remarks' => $w->remarks]);
        }
        foreach ($adjustments as $a) {
            $in  = in_array($a->type, ['increase', 'correction']) ? (float) $a->quantity_adjusted : 0;
            $out = $a->type === 'decrease' ? (float) $a->quantity_adjusted : 0;
            $balance = (float) $a->quantity_after;
            $ledger->push(['date' => $a->adjustment_date, 'type' => 'Adjustment', 'ref' => $a->adjustment_no, 'in' => $in, 'out' => $out, 'balance' => $balance, 'remarks' => $a->reason]);
        }

        $ledger = $ledger->sortBy('date')->values();

        return view('Inventory.prints.stock-card', compact('item', 'ledger'));
    }

    public function ris(InventoryRis $inventory_ri)
    {
        $inventory_ri->load(['items.item', 'requestedBy.profile', 'approvedBy.profile', 'issuedBy.profile', 'receivedBy.profile', 'status']);
        return view('Inventory.prints.ris', ['ris' => $inventory_ri]);
    }

    public function ics(InventoryIcs $inventory_ic)
    {
        $inventory_ic->load(['items.item', 'issuedTo.profile', 'issuedBy.profile', 'approvedBy.profile', 'status']);
        return view('Inventory.prints.ics', ['ics' => $inventory_ic]);
    }

    public function par(InventoryPar $inventory_par)
    {
        $inventory_par->load(['items.item', 'receivedBy.profile', 'issuedBy.profile', 'approvedBy.profile', 'status']);
        return view('Inventory.prints.par', ['par' => $inventory_par]);
    }

    public function wasteMaterial(Request $request)
    {
        $adjustments = InventoryStockAdjustment::with(['item:id,code,name', 'adjustedBy.profile', 'approvedBy.profile'])
            ->where('type', 'decrease')
            ->when($request->filled('from'), fn($q) => $q->whereDate('adjustment_date', '>=', $request->input('from')))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('adjustment_date', '<=', $request->input('to')))
            ->orderBy('adjustment_date')
            ->get();

        return view('Inventory.prints.waste-material', [
            'adjustments' => $adjustments,
            'from'        => $request->input('from'),
            'to'          => $request->input('to'),
        ]);
    }
}
