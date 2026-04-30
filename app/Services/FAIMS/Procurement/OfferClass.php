<?php

namespace App\Services\FAIMS\Procurement;
use App\Models\Procurement;
use App\Models\ListStatus;
use App\Models\ProcurementQuotationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class OfferClass
{
    public function save($request){
        $item = ProcurementQuotationItem::with('quotation')->findOrFail($request->id);
        if($item){
            $isFree = $request->boolean('is_free');
            $isNoOffer = !$isFree && $request->boolean('is_no_offer');
            $isNotApplicable = !$isFree && !$isNoOffer && $request->boolean('is_not_applicable');

            // update bid offer for bid_item
            $item->bid_price = $isFree ? 0 : ($isNoOffer || $isNotApplicable ? null : $request->bid_price);
            if (Schema::hasColumn('procurement_quotation_items', 'is_free')) {
                $item->is_free = $isFree;
            }
            if (Schema::hasColumn('procurement_quotation_items', 'is_no_offer')) {
                $item->is_no_offer = $isNoOffer;
            }
            if (Schema::hasColumn('procurement_quotation_items', 'is_not_applicable')) {
                $item->is_not_applicable = $isNotApplicable;
            }
            $item->technical_proposal = ($isNoOffer || $isNotApplicable)
                ? null
                : $request->technical_proposal;
            $item->save();
        }

         $item->quotation->update([
            'delivery_term' => $request->delivery_term,
            
         ]);

        return [
            'data' => $item,
            'message' => 'Bid Offer updated successfuly!', 
            'info' => "You've successfully updated the Bid Offer.",
        ];
    }
    
  
    public function save_bid_for_award($request){
        $procurement = Procurement::with('status')->findOrFail($request->procurement_id);
        foreach ($request->items as $item) {
            $item = ProcurementQuotationItem::findOrFail($item['id']);
            if($item){
                // update item status to "Awarded" 
                $item->status_id = ListStatus::getID('Awarded', 'Procurement');
                $item->update();
            }
        }

        foreach ($request->itemsNotAvailableForAward as $item) {
            $item = ProcurementQuotationItem::with('item')->findOrFail($item['id']);
            if($item){
                $bidPrice = (float) $item->bid_price;
                $unitCost = (float) optional($item->item)->item_unit_cost;
                $isWithinUnitCost = $unitCost <= 0 || $bidPrice <= $unitCost;

                if ($item->is_free || ($bidPrice > 0 && $isWithinUnitCost)) {
                    // update item status to "Available for Re-award" 
                    $item->status_id = ListStatus::getID('Available for Re-award', 'Procurement');
                    $item->update();
                }
                else{
                    // update item status to "Not Available for Award/Re-award" 
                    $item->status_id = ListStatus::getID('Not Available for Award/Re-award', 'Procurement');
                    $item->update();
                }
             
            }
        }
        
        //dd($procurement->status->name);
        if($procurement && $procurement->status->name === 'Rebid'){
            // update PR status to "For BAC Resolution" 
            $procurement->update([
                'sub_status_id' => ListStatus::getID('For BAC Resolution', 'Procurement'),
            ]);
        }
        else{
             // update PR status to "For BAC Resolution" 
            $procurement->update([
                'status_id' => ListStatus::getID('For BAC Resolution', 'Procurement'),
            ]);
        }


        return [
            'data' => $request->items,
            'message' => 'Bid Items awarded successfuly!', 
            'info' => "You've successfully awarded the Bid Items.",
        ];
    }

    
   
}
