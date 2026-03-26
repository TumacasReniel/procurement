<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\ProcurementBac;
use App\Models\Procurement;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementBacNoaItem;
use App\Models\ProcurementQuotation;
use App\Http\Resources\FAIMS\Procurement\ProcurementBacResource;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ListStatus;

class ProcurementBACClass
{
    public function lists($request){
        $data = ProcurementBacResource::collection(
            ProcurementBac::query()
            ->when($request->procurement_id, function ($query, $procurement_id) {
                $query->where('procurement_id', $procurement_id);
            })
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('code', 'LIKE', "%{$keyword}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status_id', $status);
            })
            ->with(['procurement', 'status'])
            ->orderBy('created_at','DESC')
            ->paginate($request->count ?? 10)
        );

        return $data;
    }

    public function save($request)
    { 
        $procurement = Procurement::with('status', 'sub_status')->findOrFail($request->procurement_id);

        switch($request->type){
            case 'Award':
                 $this->saveAwardBACResolution( $procurement, $request);
            break;
            case 'Rebid':
                $this->saveFailureBACResolution( $procurement, $request);
            break;
            case 'Re-award':
                 $this->saveReawardBACResolution( $procurement, $request);
            break;
        }

        if($procurement){
            $user = Auth::user();
            $code = ProcurementBac::generateBACResolutionNumber();
            $data = ProcurementBac::create([
                'code' => $code,
                'type' => $request->type,
                'body' => $request->body,
                'procurement_id' => $request->procurement_id,
                'created_by_id' => $user->id,
                'status_id' => ListStatus::getID('Pending','Procurement'), // set to "pending"
            ]);    
        }

        return [
            'data' =>new ProcurementBacResource($data),
            'message' => 'BAC Resolution created successfully!', 
            'info' => "You've successfully added new BAC Resolution.",
        ];
    }

    protected function saveAwardBACResolution($procurement, $request)
    { 
         if($procurement->status->name === "Rebid"){
            $procurement->update([
                'sub_status_id' => ListStatus::getID('For Approval of BAC Resolution','Procurement')  ,//set to 'For Approval of BAC Resolution'
            ]);   
        }
        else{
            // update procurement substatus to "For Quotations"
            $procurement->update([
                'status_id' => ListStatus::getID('For Approval of BAC Resolution','Procurement')  ,//set to 'For Approval of BAC Resolution'
            ]);   
        }  
    }

    protected function saveReawardBACResolution($procurement, $request)
    { 
        $procurement->update([
            'sub_status_id' => ListStatus::getID('For Approval of BAC Resolution','Procurement')  ,//set to 'For Approval of BAC Resolution'
        ]);   
    }

    protected function saveFailureBACResolution($procurement, $request)
    { 
        // update procurement substatus to "For Quotations"
        $procurement->update([
            'sub_status_id' => ListStatus::getID('For Approval of Failure BAC Resolution','Procurement')  ,//set to 'For Approval of Failure BAC Resolution'
        ]);     
        // Update related RFQs to "Failed RFQs"
        $procurement->quotations()->update([
            'status_id' =>  ListStatus::getID('Failed RFQ','Procurement'), // Failed RFQ
        ]);
        // Update related items of each quotation where item status is "Awarded" 
        foreach ($procurement->quotations->where('status_id', ListStatus::getID('Failed RFQ','Procurement')) as $quotation) {
            foreach ($quotation->items->where('status_id', ListStatus::getID('Awarded','Procurement')) as $item) {
                $item->update([
                    'is_rebid' => 1, // so that next bac resolution type "Award" will not show the items past awarded
                ]);
            }
        }
        // Update related BAC Resolutions to "Failed Biddings"
        $procurement->bac_resolutions()->update([
            'status_id' => ListStatus::getID('Failed Bidding','Procurement'), //  set to "Failed Bidding"
        ]);
    }
    
    public function update($id, $request)
    {
        $user = Auth::user();
        $data = ProcurementBac::findOrFail($id);

        $data->update([
            'body' => $request->body,
            'updated_by_id' => $user->id,
        ]);

        // Log the activity 
        activity()->performedOn($data)->causedBy($user)->log('BAC Resolution updated');

        return [
            'data' =>new ProcurementBacResource($data),
            'message' => 'BAC Resolution created successfully!',
            'info' => "You've successfully added new BAC Resolution.",
        ];
    }

       
    public function updateStatus($id, $request)
    { 
        $user = Auth::user();
        $bac_resolution = ProcurementBac::with('procurement.status' )->findOrFail($id);

        $bac_resolution->update([
            'status_id' => ListStatus::getID('Approved','Procurement'), // set status to "Approved"
        ]);

        $procurement = $bac_resolution->procurement;
        switch($procurement->status->name){
            case 'Re-award':
                 $procurement->update([
                    'sub_status_id' => ListStatus::getID('For NOA','Procurement'), // set sub_status to "For NOA"
                ]);
            break;
            case 'Rebid':
                if($procurement->sub_status->name ){
                    if($procurement->sub_status->name == 'For Approval of Failure BAC Resolution'){
                        $procurement->update([
                            'sub_status_id' => ListStatus::getID('For Quotations','Procurement'), // set sub_status to "For Quotations"
                        ]);
                    }
                    else{
                        $procurement->update([
                            'sub_status_id' => ListStatus::getID('For NOA','Procurement'), // set status to "For NOA"
                        ]);
                    }
                }
                else{
                    $procurement->update([
                        'status_id' =>  ListStatus::getID('For Quotations','Procurement'), // set sub_status to "For Quotations"
                    ]);
                }
            
            break;
            default:
                $procurement->update([
                    'status_id' => ListStatus::getID('For NOA','Procurement'), // set status to "For NOA"
                ]);

            break;
        }
        if($bac_resolution->type != "Rebid"){
       
             // create NOA and its items
            $this->createNOA($request, $bac_resolution, $user, $bac_resolution->type);
        }
        return [
            'data' =>new ProcurementBacResource($bac_resolution),
            'message' => 'BAC Resolution Status updated successfully!', 
            'info' => "You've successfully updated BAC Resolution Status.",
        ];
    }


    public function createNOA($request, $bac_resolution , $user, $bac_reso_type = null)
    {
        
        if($bac_reso_type == 'Re-award'){
            // For Re-award, create only one NOA and include all not conformed or available for re-award items from all quotations
            $procurement = $bac_resolution->procurement;
            $first_quotation_id = null;
            $all_items = [];
            foreach ($procurement->quotations as $quotation) {
                foreach ($quotation->items as $item) {
                    if($item->status_id === ListStatus::getID('Not Conformed','Procurement') ||
                       $item->status_id === ListStatus::getID('Available for Re-award','Procurement')) {
                        $all_items[] = $item;
                        if(!$first_quotation_id) {
                            $first_quotation_id = $quotation->id;
                        }
                    }
                }
            }
            if($first_quotation_id && !empty($all_items)){
                $code = ProcurementBacNoa::generateNOANumber();
                $noa = ProcurementBacNoa::create([
                    'code' => $code,
                    'procurement_id' => $bac_resolution->procurement_id,
                    'procurement_bac_id' => $bac_resolution->id,
                    'procurement_quotation_id' => $first_quotation_id,
                    'created_by_id' => $user->id,
                    'status_id' => ListStatus::getID('Pending','Procurement'), //set to "pending"
                ]);

                foreach ($all_items as $item) {
                    ProcurementBacNoaItem::create([
                        'procurement_bac_noa_id' => $noa->id,
                        'item_id' => $item->id,
                    ]);
                }
            }
        } else {
            // For Award, create one NOA per supplier (quotation); each supplier's awarded items go to their own NOA
            $awardedStatusId = ListStatus::getID('Awarded', 'Procurement');

            $quotations = collect($request->quotations ?? []);
            if ($quotations->isEmpty()) {
                // Fallback: load from procurement when request has no quotations
                $procurement = $bac_resolution->procurement()->with('quotations.items.status')->first();
                $quotations = $procurement->quotations->map(fn ($q) => [
                    'id' => $q->id,
                    'supplier_id' => $q->supplier_id,
                    'items' => $q->items->map(fn ($item) => [
                        'id' => $item->id,
                        'status_id' => $item->status_id,
                        'status' => $item->status ? ['id' => $item->status->id] : null,
                    ])->all(),
                ]);
            }

            $groupedBySupplier = $quotations->filter(function ($quotation) use ($awardedStatusId) {
                $hasAwarded = collect($quotation['items'] ?? [])->contains(function ($item) use ($awardedStatusId) {
                    $itemStatusId = $item['status_id'] ?? $item['status']['id'] ?? null;
                    return $itemStatusId == $awardedStatusId;
                });
                return $hasAwarded;
            })->groupBy(function ($quotation) {
                // Group by supplier (one NOA per supplier); fallback to quotation id if supplier_id missing
                return $quotation['supplier_id'] ?? $quotation['supplier']['id'] ?? $quotation['id'];
            });

            foreach ($groupedBySupplier as $supplierId => $supplierQuotations) {
                $firstQuotation = $supplierQuotations->first();
                $awardedItems = [];
                foreach ($supplierQuotations as $quotation) {
                    foreach ($quotation['items'] ?? [] as $item) {
                        $itemStatusId = $item['status_id'] ?? $item['status']['id'] ?? null;
                        if ($itemStatusId == $awardedStatusId) {
                            $awardedItems[] = $item;
                        }
                    }
                }
                if ($firstQuotation && !empty($awardedItems)) {
                    $code = ProcurementBacNoa::generateNOANumber();
                    $noa = ProcurementBacNoa::create([
                        'code' => $code,
                        'procurement_id' => $bac_resolution->procurement_id,
                        'procurement_bac_id' => $bac_resolution->id,
                        'procurement_quotation_id' => $firstQuotation['id'],
                        'created_by_id' => $user->id,
                        'status_id' => ListStatus::getID('Pending', 'Procurement'), // set to "pending"
                    ]);
                    foreach ($awardedItems as $item) {
                        ProcurementBacNoaItem::create([
                            'procurement_bac_noa_id' => $noa->id,
                            'item_id' => $item['id'],
                        ]);
                    }
                }
            }
        }
    }




        



    


    

   
}
