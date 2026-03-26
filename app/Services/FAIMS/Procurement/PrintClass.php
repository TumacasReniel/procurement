<?php

namespace App\Services\FAIMS\Procurement;

use App\Services\DropdownClass;
use App\Models\Procurement;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementBac;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoNtp;

use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use App\Http\Resources\FAIMS\Procurement\ProcurementQuotationResource;
use Illuminate\Support\Facades\Auth;
use NumberFormatter;



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
                return $this->printIAR($id);
            break;
        }
    }

    public function printPR($id){
        $procurement = Procurement::with('division','unit','fund_cluster','items.item_unit_type' , 'items' , 'requested_by.org_chart' , 'approved_by.org_chart' )->findOrFail($id); // 
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
        $quotation = ProcurementQuotation::with('supplier.address', 'supply_officer.profile', 'items' , 'procurement')->findOrFail($id); 

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

        $procurement = Procurement::findOrFail($id);

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

        $total_contract_amount =  $ntp->po->noa
                                ->items
                                ->sum('item.bid_price');
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

    public function printIAR($id){
        $purchase_order = ProcurementNoaPo::with('noa.procurement_bac.procurement.codes' , 'noa.procurement_quotation.supplier', 'noa.items.item', 'iar', 'noa.procurement_quotation.supply_officer.profile' )->findOrFail($id); // 
        $procurement =  $purchase_order->noa->procurement_bac->procurement;
        $codes = $procurement->codes;
        $items = $purchase_order->noa->items;
        $supplier = $purchase_order->noa->procurement_quotation->supplier;

        $total_amount = $items->sum(function ($item) {
            return $item->item->bid_price * $item->item->item->item_quantity;
        });

        $amount_to_words = $this->numberToWords($total_amount );

        // Assignatories
        $iar_members = $this->dropdown->iar_members();
        $iar_chairperson = $this->dropdown->iar_chairperson();
        $iar_vice_chairperson = $this->dropdown->iar_vice_chairperson();
        $regional_director = $this->dropdown->regional_director();
        $supply_officer =   $purchase_order->noa->procurement_quotation->supply_officer->profile;

        $array = [
            'iar' => $purchase_order?->iar,
            'purchase_order' => $purchase_order,
            'supplier' => $supplier, 
             'procurement' => $procurement, 
            'codes' => $codes,
            'items' => $items,
            'amount_to_words' => $amount_to_words,
            'regional_director' => $regional_director, 
            'supply_officer' => $supply_officer,
            'iar_chairperson' => $iar_chairperson, 
            'iar_vice_chairperson' => $iar_vice_chairperson, 
            'iar_members' => $iar_members, 
        ];

        $pdf = \PDF::loadView('FAIMS.Procurement.prints.iar',$array)->setPaper('A4', 'portrait')
                    ->setOption([
                        'isPhpEnabled' => true,
                        'isRemoteEnabled' => true 
                    ]);
         return $pdf->stream($purchase_order?->iar?->code.'.pdf');

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

      

    




}
