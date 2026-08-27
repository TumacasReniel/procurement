<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryIcs;
use App\Models\InventoryItem;
use App\Models\InventoryPar;
use App\Models\InventoryReport;
use App\Models\InventoryRis;
use App\Models\InventoryStockAdjustment;
use App\Services\Inventory\InventoryStockClass;
use Illuminate\Http\Request;

class InventoryPrintController extends Controller
{
    public function __construct(private InventoryStockClass $inventory) {}

    public function stockCard(InventoryItem $item)
    {
        $item->load(['category', 'stocks.unit']);

        $ledger = $this->inventory->stockCardLedger($item);

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

    public function report(InventoryReport $inventory_report)
    {
        $inventory_report->load(['title', 'category', 'creator.profile']);
        $detail = $this->inventory->reportDetailRows($inventory_report);

        $pdf = \PDF::loadView('Inventory.prints.report', [
            'report'          => $inventory_report,
            'kind'            => $detail['kind'],
            'columns'         => $detail['columns'],
            'rows'            => $detail['rows'],
            'groups'          => $detail['groups'] ?? [],
            'grand_total'     => $detail['grand_total'] ?? 0,
            'category_totals' => $detail['category_totals'] ?? [],
            'supply_officer'  => $detail['supply_officer'] ?? null,
            'accountant'      => $detail['accountant'] ?? null,
        ])
            ->setPaper('A4', $detail['kind'] === 'ris_issued' ? 'legal' : 'portrait')
            ->setOption([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream($inventory_report->code.'.pdf');
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
