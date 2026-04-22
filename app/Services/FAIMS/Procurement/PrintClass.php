<?php

namespace App\Services\FAIMS\Procurement;

use App\Services\DropdownClass;
use App\Models\Procurement;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementBac;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoNtp;
use App\Models\ListDropdown;
use App\Models\OrgChart;
use App\Models\User;

use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use App\Http\Resources\FAIMS\Procurement\ProcurementQuotationResource;
use Illuminate\Support\Facades\Auth;
use NumberFormatter;
use Carbon\Carbon;



class PrintClass
{
    public function __construct( DropdownClass $dropdown){
        $this->dropdown = $dropdown;
    }

    public function print($id, $request){
        switch($request->type){
            case 'procurement':
                return $this->printPR($id);
            break;
            case 'quotations':
                return $this->printQuotations($id);
            break;
            case 'abstract_of_bids':
                return $this->printAOB($id);
            break;
            case 'bac_resolution':
                return $this->printBACResolution($id);
            break;
            case 'notice_of_awards':
                return $this->printNoticeOfAward($id);
            break;
            case 'noa':
                return $this->printNoticeOfAward($id);
            break;
            case 'purchase_order':
                return $this->printPO($id);
            break;
            case 'notice_to_proceed':
                return $this->printNTP($id);
            break;
            case 'iar':
                return $this->printIAR($id, $request);
            break;
        }
    }

    public function printReport($request){
        $reportType = $request->report_type ?: 'goods_and_services';
        $dateFilterType = $request->date_filter_type ?: 'monthly';
        $year = (int) ($request->year ?: now()->year);
        $month = str_pad((string) ($request->month ?: now()->format('m')), 2, '0', STR_PAD_LEFT);
        $quarter = $request->quarter ?: 'Q1';

        $query = Procurement::with([
            'status',
            'codes.procurement_code.mode_of_procurement',
        ])
        ->when($request->keyword, function ($query, $keyword) {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('code', 'LIKE', "%{$keyword}%")
                    ->orWhere('date', 'LIKE', "%{$keyword}%")
                    ->orWhere('created_at', 'LIKE', "%{$keyword}%")
                    ->orWhere('updated_at', 'LIKE', "%{$keyword}%");
            });
        })
        ->when($request->status, function ($query, $status) {
            $query->where('status_id', $status);
        });

        $modeNames = $this->modeNamesForReportType($reportType);
        if (empty($modeNames)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereHas('codes.procurement_code.mode_of_procurement', function ($modeQuery) use ($modeNames) {
                $modeQuery->whereIn('name', $modeNames);
            });
        }

        $procurements = $query->orderBy('created_at', 'DESC')->get()->filter(function ($item) use ($dateFilterType, $year, $month, $quarter, $request) {
            if (!$item->date) {
                return true;
            }

            $itemDate = $this->parseReportDate($item->date);
            if (!$itemDate) {
                return true;
            }

            if ($dateFilterType === 'quarterly') {
                $monthNumber = $itemDate->month;
                $itemQuarter = $monthNumber <= 3 ? 'Q1' : ($monthNumber <= 6 ? 'Q2' : ($monthNumber <= 9 ? 'Q3' : 'Q4'));

                return $itemQuarter === $quarter && $itemDate->year === $year;
            }

            if ($dateFilterType === 'yearly') {
                return $itemDate->year === $year;
            }

            if ($dateFilterType === 'range') {
                $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
                $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

                if ($startDate && $itemDate->lt($startDate)) {
                    return false;
                }

                if ($endDate && $itemDate->gt($endDate)) {
                    return false;
                }

                return true;
            }

            return $itemDate->year === $year && $itemDate->format('m') === $month;
        })->values();

        $signatories = $this->reportSignatories();
        $preparedBy = collect($signatories['prepared_by'] ?? [])->values();
        if ($preparedBy->isEmpty()) {
            $preparedBy = collect([
                ['name' => 'N/A', 'role' => 'Procurement Staff'],
            ]);
        }

        $array = [
            'procurements' => $procurements,
            'report_type' => $reportType,
            'date_filter_type' => $dateFilterType,
            'month' => $month,
            'quarter' => $quarter,
            'year' => $year,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'signatories' => $signatories,
            'prepared_by_signatories' => $preparedBy,
            'supply_officer' => $signatories['supply_officer'] ?? null,
            'noted_by' => $signatories['noted_by'] ?? null,
            'fo_label' => $this->foLabelForReportType($reportType),
            'report_subtitle' => $this->reportSubtitleForType($reportType),
            'report_period_label' => $this->reportPeriodLabel($dateFilterType, $month, $quarter, $year, $request->start_date, $request->end_date),
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.procurement-report', $array)
            ->setPaper('A4', 'landscape')
            ->setOption([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream('procurement-report-'.$this->fileSafePeriodLabel($dateFilterType, $month, $quarter, $year, $request->start_date, $request->end_date).'.pdf');
    }

    public function printPR($id){
        $procurement = Procurement::with('division','unit.responsibility_center','fund_cluster','items.item_unit_type' , 'items' , 'requested_by.org_chart' , 'approved_by.org_chart', 'comments.user.profile' )->findOrFail($id); // 
        $items = $procurement->items;
        $regional_director = $this->dropdown->regional_director();



        $array = [
            'procurement' => $procurement,
            'items' => $items,
            'regional_director' => $regional_director,
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.procurement-request',$array)->setPaper('A4', 'portrait')
                ->setOption([
                        'isPhpEnabled' => true,
                        'isRemoteEnabled' => true 
                    ]);
        return $pdf->stream($procurement->code.'.pdf');

    }


    public function printQuotations($id){
        $quotation = ProcurementQuotation::with('supplier.address', 'supplier.attachments', 'supply_officer.profile', 'items' , 'procurement')->findOrFail($id); 

        $array = [
            'quotation' => $quotation,
            'procurement' => $quotation->procurement,
            'supplier' => $quotation->supplier,
            'supply_officer' => $quotation->supply_officer,
            'items' => $quotation->items,
            'regional_director' => $this->dropdown->regional_director(),
        ];

        // Add setOption here to enable PHP-based page counting
        $pdf = \PDF::loadView('FAIMS.Procurement.prints.quotations', $array)
            ->setPaper('A4', 'portrait')
            ->setOption([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true 
            ]);

        return $pdf->stream($quotation->code.'.pdf');
    }

     public function printAOB($id){
        $quotations = ProcurementQuotation::with('supplier.address', 'supply_officer.profile', 'items' , 'procurement')
                                        ->whereNot('status_id', 71) // status is not  "Failed RFQs"
                                        ->where('procurement_id', $id)->get(); // 

        $procurement = Procurement::with('division', 'unit')->findOrFail($id);

        $regional_director = $this->dropdown->regional_director();
        $bac_chairperson = $this->dropdown->bac_chairperson();
        $bac_vice_chairperson = $this->dropdown->bac_vice_chairperson();
        $bac_members = $this->dropdown->bac_members();

        $array = [
            'quotations' => $quotations,
            'procurement' => $procurement,
            'bac_chairperson' => $bac_chairperson,
            'bac_vice_chairperson' => $bac_vice_chairperson,
            'bac_members' => $bac_members,
            'regional_director' => $regional_director,
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.abstract-of-bids',$array)->setPaper('A4', 'landscape')
                        ->setOption([
                            'isPhpEnabled' => true,
                            'isRemoteEnabled' => true 
                        ]);
        return $pdf->stream($procurement->code.'.pdf');

    }

    public function printBACResolution($id){
        $bac_resolution = ProcurementBac::findOrFail($id); // 
        $bac_members = $this->dropdown->bac_members();
        $bac_chairperson = $this->dropdown->bac_chairperson();
        $bac_vice_chairperson = $this->dropdown->bac_vice_chairperson();
        $regional_director = $this->dropdown->regional_director();

        $array = [
            'bac_resolution' => $bac_resolution,
            'bac_chairperson' => $bac_chairperson,
            'bac_vice_chairperson' => $bac_vice_chairperson,
            'bac_members' => $bac_members,
            'regional_director' => $regional_director,
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.bac-resolution',$array)->setPaper('A4', 'portrait')
                    ->setOption([
                        'isPhpEnabled' => true,
                        'isRemoteEnabled' => true 
                    ]);
        return $pdf->stream($bac_resolution->code.'.pdf');

    }

    public function printNoticeOfAward($id){
        $notice_of_award = ProcurementBacNoa::with('procurement_quotation.supplier.address', 
                                                    'procurement_quotation.supplier.conformes' , 
                                                    'procurement_quotation.procurement' , 
                                                    'procurement_quotation.items', 'items')
                                                    ->findOrFail($id); // 

        $item_nos = $notice_of_award->items->pluck('item.item.item_no');

        $total_contract_amount = $notice_of_award
                                ->items
                                ->sum('item.bid_price');
        // set amoutn format   
        $total_amount = number_format($total_contract_amount, 2);
        $amount_to_words = $this->numberToWords($total_contract_amount );


        $procurement =  $notice_of_award->procurement_quotation->procurement;
        $regional_director = $this->dropdown->regional_director();

        $array = [
            'noa' => $notice_of_award,
            'regional_director' => $regional_director, 
            'procurement' => $procurement, 
            'item_nos' => $item_nos,
            'total_amount' => $total_amount,
            'amount_to_words' => $amount_to_words,
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.notice-of-award',$array)->setPaper('A4', 'portrait')
                ->setOption([
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true 
                ]);
         return $pdf->stream($notice_of_award->code.'.pdf');

    }

    public function printPO($id){
        $purchase_order = ProcurementNoaPo::with('noa.procurement_bac.procurement.codes' , 'noa.procurement_quotation.supplier', 'noa.items.item' )->findOrFail($id); // 
        $procurement =  $purchase_order->noa->procurement_bac->procurement;
        $codes = $procurement->codes;
        $items = $purchase_order->noa->items;
        $supplier = $purchase_order->noa->procurement_quotation->supplier;

        $total_amount = $items->sum(function ($item) {
            return $item->item->bid_price * $item->item->item->item_quantity;
        });

        $amount_to_words = $this->numberToWords($total_amount );

        $regional_director = $this->dropdown->regional_director();
        $chief_accountant = $this->dropdown->chief_accountant();

        $array = [
            'purchase_order' => $purchase_order,
            'supplier' => $supplier, 
             'procurement' => $procurement, 
            'codes' => $codes,
            'items' => $items,
            'amount_to_words' => $amount_to_words,
            'regional_director' => $regional_director, 
            'chief_accountant' => $chief_accountant, 
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.purchase-order',$array)->setPaper('A4', 'portrait')
                ->setOption([
                        'isPhpEnabled' => true,
                        'isRemoteEnabled' => true 
                    ]);
         return $pdf->stream($purchase_order->code.'.pdf');

    }

     public function printNTP($id){
        $ntp = ProcurementPoNtp::with('po.noa.procurement_quotation.procurement')->where('po_id', $id)->first(); // 

        $total_contract_amount = $ntp->po->noa
            ->items
            ->sum(function ($item) {
                return ($item->item->bid_price ?? 0) * ($item->item->item->item_quantity ?? 0);
            });
        // set amoutn format   
        $total_amount = number_format($total_contract_amount, 2);
        $amount_to_words = $this->numberToWords($total_contract_amount );

        $po =  $ntp->po;
        $noa = $ntp->po->noa;
        $quotation =  $ntp->po->noa->procurement_quotation;
        $procurement =  $ntp->po->noa->procurement_quotation->procurement;

        $regional_director = $this->dropdown->regional_director();

        $array = [
            'ntp' => $ntp,
            'po' => $po,
            'noa' => $noa,
            'quotation' => $quotation,
            'procurement' => $procurement,
            'total_amount' => $total_amount,
            'amount_to_words' => $amount_to_words,
            'regional_director' => $regional_director, 
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.notice-to-proceed',$array)->setPaper('A4', 'portrait')
                 ->setOption([
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true 
                ]);
         return $pdf->stream($ntp->code.'.pdf');

    }

    public function printIAR($id, $request = null){
        $purchase_order = ProcurementNoaPo::with(
            'noa.procurement_bac.procurement.codes',
            'noa.procurement_bac.procurement.unit.responsibility_center',
            'noa.procurement_bac.procurement.division',
            'noa.procurement_quotation.supplier',
            'noa.items.item',
            'iar.status',
            'iar.inspected_by.profile',
            'iars.status',
            'iars.inspected_by.profile',
            'noa.procurement_quotation.supply_officer.profile'
        )->findOrFail($id); // 
        $procurement =  $purchase_order->noa->procurement_bac->procurement;
        $codes = $procurement->codes;
        $allItems = $purchase_order->noa->items;
        $selectedIarId = (int) data_get($request, 'iar_id', 0);
        $iar = $selectedIarId > 0
            ? $purchase_order->iars->firstWhere('id', $selectedIarId)
            : $purchase_order->iar;
        $savedDeliveries = $iar
            ? $iar->normalizedDeliveredItems($allItems)
            : collect();
        $deliveryDate = $iar?->created_at?->copy()->startOfDay()
            ?? $purchase_order->actual_delivery_date?->copy()->startOfDay();
        $savedDeliveryMap = $savedDeliveries->keyBy('item_id');
        $hasSavedDeliveries = $savedDeliveries->isNotEmpty();
        $items = $allItems
            ->filter(fn ($item) => !$hasSavedDeliveries || $savedDeliveryMap->has((int) $item->id))
            ->values()
            ->map(function ($item) use ($savedDeliveryMap, $hasSavedDeliveries) {
                $orderedQuantity = (float) data_get($item, 'item.item.item_quantity', 0);
                $deliveredQuantity = $hasSavedDeliveries
                    ? (float) data_get($savedDeliveryMap->get((int) $item->id), 'delivered_quantity', $orderedQuantity)
                    : $orderedQuantity;

                $item->ordered_quantity = $orderedQuantity;
                $item->delivered_quantity = $deliveredQuantity;
                $item->line_total = $item->item->bid_price * $deliveredQuantity;

                return $item;
            });
        $supplier = $purchase_order->noa->procurement_quotation->supplier;

        $total_amount = $items->sum(fn ($item) => (float) ($item->line_total ?? 0));

        $amount_to_words = $this->numberToWords($total_amount );

        // Assignatories
        $iar_members = $this->dropdown->iar_members();
        $iar_chairperson = $this->dropdown->iar_chairperson();
        $regional_director = $this->dropdown->regional_director();
        $supply_officer =   $purchase_order->noa->procurement_quotation->supply_officer->profile;
        $rcCode = $procurement->unit?->responsibility_center_code;
        $inspectedByName = trim((string) (
            data_get($iar, 'inspected_by.profile.fullname')
            ?: data_get($iar, 'inspected_by.profile.full_name')
            ?: data_get($iar, 'inspected_by.profile.name')
            ?: data_get($iar, 'inspected_by.name')
            ?: data_get($iar, 'inspected_by.username')
            ?: ''
        ));

        $array = [
            'iar' => $iar,
            'delivery_date' => $deliveryDate,
            'purchase_order' => $purchase_order,
            'supplier' => $supplier, 
             'procurement' => $procurement, 
            'codes' => $codes,
            'items' => $items,
            'is_partial_delivery' => $items->count() !== $allItems->count()
                || $items->contains(fn ($item) => (float) ($item->delivered_quantity ?? 0) < (float) ($item->ordered_quantity ?? 0)),
            'amount_to_words' => $amount_to_words,
            'regional_director' => $regional_director, 
            'supply_officer' => $supply_officer,
            'rc_code' => $rcCode,
            'inspected_by_name' => $inspectedByName,
            'iar_chairperson' => $iar_chairperson, 
            'iar_members' => $iar_members, 
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.iar',$array)->setPaper('A4', 'portrait')
                    ->setOption([
                        'isPhpEnabled' => true,
                        'isRemoteEnabled' => true 
                    ]);
         return $pdf->stream(($iar?->code ?? $purchase_order->code . '-iar').'.pdf');

    }



   private function numberToWords($number)
{
    // Make sure $number is a float
    $number = (float) $number;

    $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);

    $whole = floor($number);
    $decimal = round(($number - $whole) * 100);

    $words = ucfirst($formatter->format($whole)) . ' Pesos';

    if ($decimal > 0) {
        $words .= ' and ' . $formatter->format($decimal) . ' Centavos';
    }

    return ucwords($words);
}

    private function modeNamesForReportType($reportType): array
    {
        $map = [
            'goods_and_services' => [
                'Competitive Public Bidding',
                'Limited Source Bidding',
                'Direct Contracting',
                'Repeat Order',
                'Shopping',
                'Negotiated Procurement',
                'Small Value Procurement',
                'Lease of Venue and Community Facilities',
                'Agency-to-Agency',
            ],
            'infrastructure' => [
                'Competitive Public Bidding',
                'Limited Source Bidding',
                'Direct Contracting',
                'Negotiated Procurement',
                'Agency-to-Agency',
            ],
            'consulting' => [
                'Competitive Public Bidding',
                'Limited Source Bidding',
                'Negotiated Procurement',
            ],
        ];

        return $map[$reportType] ?? [];
    }

    private function reportSignatories(): array
    {
        $procurementStaff = User::with('profile')
            ->whereHas('roles', function ($query) {
                $query->where('list_roles.name', 'Procurement Staff');
            })
            ->get()
            ->map(function ($user) {
                return [
                    'name' => strtoupper($user->profile?->full_name ?? ('USER #' . $user->id)),
                    'role' => 'Procurement Staff',
                ];
            })
            ->values()
            ->all();

        $supplyOfficer = User::with('profile')
            ->whereHas('roles', function ($query) {
                $query->where('list_roles.name', 'Supply Officer');
            })
            ->first();

        $assistantRegionalDirector = OrgChart::with('user.profile', 'designation')
            ->where('designation_id', ListDropdown::getID('Assistant Regional Director', 'Designation'))
            ->first();

        return [
            'prepared_by' => array_slice($procurementStaff, 0, 2),
            'supply_officer' => $supplyOfficer ? [
                'name' => strtoupper($supplyOfficer->profile?->full_name ?? ('USER #' . $supplyOfficer->id)),
                'role' => 'Supply Officer',
            ] : null,
            'noted_by' => $assistantRegionalDirector ? [
                'name' => strtoupper($assistantRegionalDirector->user?->profile?->full_name ?? ''),
                'designation' => 'ARD-FASS',
            ] : null,
        ];
    }

    private function parseReportDate($date): ?Carbon
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date);
        }

        $raw = trim((string) $date);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            $formats = ['F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $raw);
                } catch (\Throwable $inner) {
                }
            }
        }

        return null;
    }

    private function reportSubtitleForType($reportType): string
    {
        return match ($reportType) {
            'infrastructure' => 'INFRASTRUCTURE PROJECTS',
            'consulting' => 'CONSULTING SERVICES',
            default => 'GOODS AND SERVICES',
        };
    }

    private function foLabelForReportType($reportType): string
    {
        return match ($reportType) {
            'infrastructure' => 'For Infrastructure Projects',
            'consulting' => 'For Consulting Services',
            default => 'For Goods and Services',
        };
    }

    private function reportPeriodLabel($dateFilterType, $month, $quarter, $year, $startDate, $endDate): string
    {
        if ($dateFilterType === 'quarterly') {
            return "Quarter of {$quarter}, {$year}";
        }

        if ($dateFilterType === 'yearly') {
            return "Year of {$year}";
        }

        if ($dateFilterType === 'range') {
            return 'Date Range: '.($startDate ?: '-').' to '.($endDate ?: '-');
        }

        return 'Month of '.Carbon::createFromDate($year, (int) $month, 1)->format('F Y');
    }

    private function fileSafePeriodLabel($dateFilterType, $month, $quarter, $year, $startDate, $endDate): string
    {
        if ($dateFilterType === 'quarterly') {
            return strtolower($quarter).'-'.$year;
        }

        if ($dateFilterType === 'yearly') {
            return (string) $year;
        }

        if ($dateFilterType === 'range') {
            return ($startDate ?: 'start').'-to-'.($endDate ?: 'end');
        }

        return $year.'-'.$month;
    }

      

    




}
