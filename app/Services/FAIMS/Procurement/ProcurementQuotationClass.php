<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\Procurement;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementQuotationItem;
use App\Http\Resources\FAIMS\Procurement\ProcurementQuotationResource;
use Illuminate\Support\Facades\Auth;
use App\Models\ListStatus;

class ProcurementQuotationClass
{
    public function save($request){
        $procurement = Procurement::with('status')->findOrFail($request->procurement_id);
        $supplierIds = collect($request->supplier_ids ?? [])
            ->map(function ($supplierId) {
                return is_array($supplierId) ? ($supplierId['value'] ?? $supplierId['id'] ?? null) : $supplierId;
            })
            ->filter()
            ->unique()
            ->values();

        $existingSupplierIds = ProcurementQuotation::where('procurement_id', $request->procurement_id)
            ->whereIn('supplier_id', $supplierIds)
            ->pluck('supplier_id');

        $supplierIds = $supplierIds->diff($existingSupplierIds)->values();
        $procurement_quotation = null;

        // create initial 
        foreach ($supplierIds as $supplier_id) {

            // save Request for Quotation(RFQ)
            $code= ProcurementQuotation::generateRFQNumber();
            $procurement_quotation = new ProcurementQuotation();
            $procurement_quotation->code = $code;
            $procurement_quotation->procurement_id = $request->procurement_id;
            $procurement_quotation->submission_not_later_than = $request->submission_not_later_than;
            $procurement_quotation->supply_officer_id = $request->supply_officer_id;
            $procurement_quotation->supplier_id = $supplier_id;
            $procurement_quotation->status_id = ListStatus::getID('Pending','Procurement'); // set to 'pending' 
            $procurement_quotation->save();

            // items
            foreach ($request->items as $item) {
                // create initial        
                $procurement_quotation_item = new ProcurementQuotationItem();
                $procurement_quotation_item->quotation_id = $procurement_quotation->id;
                $procurement_quotation_item->procurement_item_id = $item['id'];
                $procurement_quotation_item->status_id = ListStatus::getID('Available for Award','Procurement'); // set status to "available for award"
                $procurement_quotation_item->save();

            }

            $procurement->update([
                'quotation_count' => $procurement->quotation_count+1,
            ]);
        }

        if (!$procurement_quotation) {
            return [
                'data' => null,
                'message' => 'No new Request for Quotation created.',
                'info' => 'The selected supplier already has a Request for Quotation for this procurement.',
            ];
        }

      

        if( $procurement && $procurement->status->name === 'Rebid'){
            // update Procurement status
            $procurement->update([
                'sub_status_id' =>  ListStatus::getID('For Bids','Procurement'), // set to "For Bids"
            ]);
        }
        else{
            // update Procurement status
            $procurement->update([
                'status_id' =>  ListStatus::getID('For Bids','Procurement'), // set to "For Bids"
            ]);
        }

      


        return [
            'data' => new ProcurementQuotationResource($procurement_quotation),
            'message' => 'Request for Quotations successfuly saved!', 
            'info' => "You've successfully created the Request for Quotation.",
        ];
    }

    public function delete($id){

        // Find the RFQ by ID
        $quotation_request = ProcurementQuotation::with('procurement')->findOrFail($id);
        $procurement = $quotation_request->procurement;

        // Delete the RFQ
        $quotation_request->delete();

        if ($procurement) {
            $procurement->update([
                'quotation_count' => max(0, $procurement->quotation_count - 1),
            ]);
        }
        
        return [
            'data' => new ProcurementQuotationResource($quotation_request),
            'message' => 'Request for Quotations successfuly deleted!', 
            'info' => "You've successfully deleted the Request for Quotation.",
        ];
    }



}
