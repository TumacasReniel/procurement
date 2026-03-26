<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\ProcurementBac;
use App\Models\Procurement;
use App\Models\ProcurementItem;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementBacNoaItem;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementQuotationItem;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoNtp;
use App\Models\ProcurementPoIar;
use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Models\ListDropdown;
use App\Http\Resources\FAIMS\Procurement\ProcurementNoaPoResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ListStatus;

class ProcurementPOClass
{
    public function lists($request)
    {
        $data = ProcurementNoaPoResource::collection(
            ProcurementNoaPo::with('noa')
                ->when($request->procurement_id, function ($query, $procurement_id) {
                    $query->where('procurement_id', $procurement_id);
                })
                ->when($request->keyword, function ($query) use ($request) {
                    $keyword = $request->keyword;

                    $query->where(function ($q) use ($keyword) {
                        $q->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('noa', function ($noaQ) use ($keyword) {
                            $noaQ->where('code', 'LIKE', "%{$keyword}%");
                        });
                    });
                })
                ->when($request->status, function ($query) use ($request) {
                    $query->where('status_id', ListStatus::getID($request->status, 'Procurement'));
              
                })
                ->orderBy('created_at','DESC')
                ->paginate($request->count)
        );

        return $data;
    }


    public function purchase_order($request){
        $data =  ProcurementNoaPo::with('status')->where('noa_id', $request->noa_id)->first();
        return $data;
    }

    public function save($request)
    { 
        $user = Auth::user();
        $code = ProcurementNoaPo::generatePONumber();

        $data = ProcurementNoaPo::create([
            'procurement_id' => $request->procurement_id,
            'code' => $code,
            'po_date' => now()->toDateString(),
            'payment_term' => $request->payment_term,
            'delivery_term' => $request->delivery_term,
            'noa_id' => $request->noa_id,
            'place_of_delivery_id' => $request->place_of_delivery_id,
            'date_of_delivery' => $request->date_of_delivery,
            'created_by_id' => $user->id,
            'status_id' => ListStatus::getID('Created','Procurement'), // set to "Created"
        ]);

        $noa = ProcurementBacNoa::with('procurement_bac.procurement')->findOrFail($request->noa_id);
        $procurement = $noa->procurement_bac->procurement;
        $current_pr_status = $procurement->status_id;
        
        if($noa){
             // update PR status to "PO Created" 
             $noa->status_id = ListStatus::getID('PO Created','Procurement');
             $noa->update();

            // update PR status to "PO Created" 
            $procurement =  $noa->procurement_bac->procurement;
            if( $procurement->status_id == ListStatus::getID('Rebid','Procurement') || $procurement->status_id == ListStatus::getID('Re-award','Procurement')){
                $updated_pr_substatus = $noa->procurement_bac->overall_substatus($current_pr_status);
                // update Procurement Request SubStatus (only if we get a valid status)
                if($updated_pr_substatus !== null && is_numeric($updated_pr_substatus)){
                    $procurement->update([
                        'sub_status_id' =>  $updated_pr_substatus,
                    ]);
                }
            }
            else{
                $updated_pr_status = $noa->procurement_bac->overall_status($current_pr_status);
                // update Procurement Request Status (only if we get a valid status)
                if($updated_pr_status !== null && is_numeric($updated_pr_status)){
                    $procurement->update([
                        'status_id' =>  $updated_pr_status,
                        'sub_status_id'=>null,
                    ]);
                }
            }
   
          
  
        }

      



        return [
            'data' =>new ProcurementNoaPoResource($data),
            'message' => 'BAC Resolution created successfully!', 
            'info' => "You've successfully added new BAC Resolution.",
        ];
    }

    
    public function update($id, $request)
    { 
        $user = Auth::user();
        $data = ProcurementNoaPo::findOrFail($id);

        $data->update([
            'body' => $request->body,
            'updated_by_id' => $user->id,
        ]);

        return [
            'data' =>new ProcurementNoaPoResource($data),
            'message' => 'BAC Resolution created successfully!', 
            'info' => "You've successfully added new BAC Resolution.",
        ];
    }

       
       
    public function updateStatus($id, $request)
    { 
 
        $user = Auth::user();
        $po = ProcurementNoaPo::with('noa.procurement_bac.procurement')->findOrFail($id);
        $current_pr_status = $po->noa->procurement_bac->procurement->status_id;
        $procurement =  $po->noa->procurement_bac->procurement;
        $statusPayload = $request->input('status');
        $currentStatusName = is_array($statusPayload)
            ? ($statusPayload['name'] ?? null)
            : (is_object($statusPayload) ? ($statusPayload->name ?? null) : null);

        if (!$currentStatusName) {
            return [
                'data' => new ProcurementNoaPoResource($po),
                'message' => 'Missing status payload.',
                'info' => 'No status update was applied.',
                'status' => 'warning',
            ];
        }

        // Update PO/NOA status FIRST based on the requested status
        if($currentStatusName == "Created"){

             $po->update([
                'status_id' => ListStatus::getID('Issued','Procurement'), // set status to "Issued"
            ]);
            $po->noa->update([
                'status_id' => ListStatus::getID('PO Issued','Procurement'), // set noa status to "PO Issued"
            ]);
        }
        else if($currentStatusName == "Issued"){
        
       
            $po->update([
                'status_id' => ListStatus::getID('Conformed','Procurement'), // set status to "Conformed"  
            ]);

            $po->noa->update([
                'status_id' => ListStatus::getID('PO Conformed','Procurement'), // set noa status to "PO Conformed"
            ]);

            // create Notice to Proceed(NTP) 
            $this->createNTP($request, $po, $user);
        }
        else if($currentStatusName == "Conformed"){
             $po->update([
                'status_id' => ListStatus::getID('Delivered/For Inspection','Procurement'), // set status to "Delivered/For Inspection"
            ]);
            $po->noa->update([
                'status_id' => ListStatus::getID('PO Delivered/For Inspection','Procurement'), // set noa status to "PO Delivered/For Inspection"
            ]);
            //Create IAR
            $this->createIAR($po);
        }
        else if($currentStatusName == "Delivered/For Inspection"){
             $po->update([
                'status_id' => ListStatus::getID('Completed','Procurement'), // set status to "Completed"

            ]);
            $po->noa->update([
                'status_id' => ListStatus::getID('Completed','Procurement'), // set status to "Completed"
            ]);


           // update Quotation items status to "Completed" only items which is related to the noa
           $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $po->noa->id)->get();
           foreach ($noa_items as $noa_item) {
               $quotation_item = $noa_item->item;
               $quotation_item->update([
                   'status_id' => ListStatus::getID('Completed','Procurement')
               ]);

           }

           $item_ids = $noa_items->pluck('item.procurement_item_id')->unique();

           // update ProcurementItem status to "Completed" only for items related to the NOA
           ProcurementItem::whereIn('id', $item_ids)->update(['status_id' => ListStatus::getID('Completed','Procurement')]);

           // sync completed procurement items to inventory stocks
           $this->syncCompletedItemsToInventory($po, $item_ids);

           // update also the items in with the same item no in the related quotation items to "Not Awarded"
           ProcurementQuotationItem::whereHas('quotation', function($q) use ($po) {
               $q->where('procurement_id', $po->procurement_id);
           })->whereIn('procurement_item_id', $item_ids)
           ->where(function($q) {
               $q->where('status_id', ListStatus::getID('Available for Re-award','Procurement'))
                 ->orWhere('status_id', ListStatus::getID('Awarded','Procurement'));
           })
           ->update(['status_id' => ListStatus::getID('Not Awarded','Procurement')]);

            // check Procurement Items if all items are Completed else Partially Completed
            if ($procurement->items->every(fn($item) => $item->status_id == ListStatus::getID('Completed','Procurement'))) {
                // If PR is currently Rebid or Re-award, set both status and sub_status to Completed
                if($current_pr_status == ListStatus::getID('Re-award','Procurement') || $current_pr_status == ListStatus::getID('Rebid','Procurement')){
                    $procurement->update([
                        'status_id' => ListStatus::getID('Completed','Procurement'),
                        'sub_status_id' => ListStatus::getID('Completed','Procurement'),
                    ]);
                }
                else{
                    $procurement->update([
                        'status_id' => ListStatus::getID('Completed','Procurement'),
                        'sub_status_id' => null,
                    ]);
                }
            }
            else{
                // If PR is currently Rebid or Re-award, set both status and sub_status to Partially Completed
                if($current_pr_status == ListStatus::getID('Re-award','Procurement') || $current_pr_status == ListStatus::getID('Rebid','Procurement')){
                    $procurement->update([
                        'status_id' => ListStatus::getID('Partially Completed','Procurement'),
                        'sub_status_id' => ListStatus::getID('Partially Completed','Procurement'),
                    ]);
                }
                else{
                    // update Procurement Request SubStatus
                    $procurement->update([
                        'status_id' => ListStatus::getID('Partially Completed','Procurement'),
                        'sub_status_id'=> null,
                    ]);
                }
            
                
            }
        }

        // Update PR status AFTER PO/NOA status has been updated
        // Refresh the NOA to get the updated status
        $po->refresh();
        $po->noa->refresh();
        
        // Use the NEW NOA status to determine PR status
        $new_noa_status = $po->noa->status->name;
        
        // Skip PR status update if NOA status is "Completed" (already handled above)
        if($new_noa_status != 'Completed'){
            // if current_pr_status "Re-award" or "Rebid"
            if($current_pr_status == ListStatus::getID('Re-award','Procurement') || $current_pr_status == ListStatus::getID('Rebid','Procurement')){
                $updated_pr_substatus = $po->noa->procurement_bac->overall_substatus($current_pr_status);
                // update Procurement Request SubStatus
                $procurement->update([
                    'sub_status_id' =>  $updated_pr_substatus,
                ]);

            }
            else{
                $updated_pr_status = $po->noa->procurement_bac->overall_status($current_pr_status);
                // update Procurement Request Status
                $procurement->update([
                    'status_id' =>  $updated_pr_status,
                    'sub_status_id'=>null,
                ]);
            }
        }

        return [
            'data' =>new ProcurementNoaPoResource($po),
            'message' => 'Purchase Order Status updated successfully!', 
            'info' => "You've successfully updated Purchase Order Status.",
        ];
    }

    Public function createIAR($po)
    { 
        // Loop through each awarded quotation
            $code = ProcurementPoIar::generateIARNumber();
            $iar = ProcurementPoIar::create([
                'procurement_id' => $po->procurement_id,
                'code' => $code,
                'po_id' => $po->id,
            ]);
    }


    public function createNTP($request, $po , $user)
    { 
        // Loop through each awarded quotation
            $code = ProcurementPoNtp::generateNTPNumber();
            $noa = ProcurementPoNtp::create([
                'procurement_id' => $po->procurement_id,
                'code' => $code,
                'po_id' => $po->id,
                'created_by_id' => $user->id,
                'status_id' => ListStatus::getID('Pending','Procurement'), //set status to "Pending"
            ]);
    }


    public function notConformed($id, $request)
    { 
   
        $user = Auth::user();
        $po = ProcurementNoaPo::with('noa.procurement_bac.procurement' , 'status')->findOrFail($id);

        $po->update([
            'status_id' => ListStatus::getID('Not Conformed','Procurement'), // set status to "Not Conformed"
        ]);     

        $po->noa->update([
            'status_id' => ListStatus::getID('PO Not Conformed','Procurement'), // set noa status to "PO Not Conformed"
        ]);
    

        $po->noa->procurement_bac->update([
            'status_id' => ListStatus::getID('Not Conformed','Procurement'), // set bac resolution status to "PO Not Conformed"
        ]);

        // update quotation items in the specific quotation to "Awarded" for re-award
        $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $po->noa->id)->get();
        $item_ids = $noa_items->pluck('item.procurement_item_id')->unique();
        ProcurementQuotationItem::where('quotation_id', $po->noa->procurement_quotation_id)
        ->whereIn('procurement_item_id', $item_ids)
        ->where(function($q) {
            $q->where('status_id', ListStatus::getID('Available for Re-award','Procurement'))
              ->orWhere('status_id', ListStatus::getID('Awarded','Procurement'));
        })
        ->update(['status_id' => ListStatus::getID('Awarded','Procurement')]);

        // create comments for reason
        $po->comments()->create([
            'content' => $request->comment,
            'user_id' => $user->id,
        ]);

        $procurement = $po->noa->procurement_bac->procurement;
        $current_pr_status = $po->noa->procurement_bac->procurement->status_id;

       // Determine if rebid or reaward
        $updated_pr_status = $po->noa->procurement_bac->determine_re_award_or_rebid();
        $procurement->update([
            'status_id' => $updated_pr_status,
        ]);

        // Handle re-award/rebid logic if applicable
        if($updated_pr_status == ListStatus::getID('Re-award','Procurement')){
            $procurement->update([
                'reawarded_count' => $procurement->reawarded_count + 1,
            ]);
        }
        else if($updated_pr_status == ListStatus::getID('Rebid','Procurement')){
            $procurement->update([
                'rebidded_count' => $procurement->rebidded_count + 1,
            ]);
        }
        return [
            'data' =>new ProcurementNoaPoResource($po),
            'message' => 'Purchase Order Status updated successfully!', 
            'info' => "You've successfully updated Purchase Order Status.",
        ];
    }

    public function revertStatus($id, $request)
    {
        $po = ProcurementNoaPo::with('noa.procurement_bac.procurement', 'status', 'noa.status')->findOrFail($id);
        $procurement = $po->noa->procurement_bac->procurement;
        $current_pr_status = $procurement->status_id;

        $currentStatusName = $po->status?->name;
        $revertedPoStatus = null;
        $revertedNoaStatus = null;

        if ($currentStatusName === 'Completed') {
            // rollback previously posted inventory quantities before reverting status
            $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $po->noa->id)->get();
            $item_ids = $noa_items->pluck('item.procurement_item_id')->unique()->filter()->values();
            $this->rollbackCompletedItemsFromInventory($po, $item_ids);

            $revertedPoStatus = 'Delivered/For Inspection';
            $revertedNoaStatus = 'PO Delivered/For Inspection';
        } elseif ($currentStatusName === 'Delivered/For Inspection') {
            $revertedPoStatus = 'Conformed';
            $revertedNoaStatus = 'PO Conformed';
        } elseif ($currentStatusName === 'Conformed') {
            $revertedPoStatus = 'Issued';
            $revertedNoaStatus = 'PO Issued';
        } elseif ($currentStatusName === 'Issued') {
            $revertedPoStatus = 'Created';
            $revertedNoaStatus = 'PO Created';
        }

        if (!$revertedPoStatus || !$revertedNoaStatus) {
            return [
                'data' => new ProcurementNoaPoResource($po),
                'message' => 'PO status cannot be reverted from the current step.',
                'info' => 'Nothing changed.',
                'status' => 'warning',
            ];
        }

        $po->update([
            'status_id' => ListStatus::getID($revertedPoStatus, 'Procurement'),
        ]);

        $po->noa->update([
            'status_id' => ListStatus::getID($revertedNoaStatus, 'Procurement'),
        ]);

        $po->refresh();
        $po->noa->refresh();

        if (
            $current_pr_status == ListStatus::getID('Re-award', 'Procurement') ||
            $current_pr_status == ListStatus::getID('Rebid', 'Procurement')
        ) {
            $updated_pr_substatus = $po->noa->procurement_bac->overall_substatus($current_pr_status);
            $procurement->update([
                'sub_status_id' => $updated_pr_substatus,
            ]);
        } else {
            $updated_pr_status = $po->noa->procurement_bac->overall_status($current_pr_status);
            $procurement->update([
                'status_id' => $updated_pr_status,
                'sub_status_id' => null,
            ]);
        }

        return [
            'data' => new ProcurementNoaPoResource($po),
            'message' => 'Purchase Order Status reverted successfully!',
            'info' => "You've successfully reverted Purchase Order Status.",
        ];
    }


    private function syncCompletedItemsToInventory(ProcurementNoaPo $po, $procurementItemIds): void
    {
        $itemIds = collect($procurementItemIds)->filter()->unique()->values();
        if ($itemIds->isEmpty()) {
            return;
        }

        $categoryId = $this->defaultInventoryCategoryId();
        if (!$categoryId) {
            Log::warning('Inventory sync skipped: missing Inventory Category dropdown.', [
                'po_id' => $po->id,
                'procurement_id' => $po->procurement_id,
            ]);
            return;
        }

        $items = ProcurementItem::with('item_unit_type')
            ->whereIn('id', $itemIds)
            ->get();

        foreach ($items as $item) {
            $unitId = $this->resolveInventoryUnitIdFromProcurementItem($item);
            if (!$unitId) {
                Log::warning('Inventory sync skipped item: unit mapping not found.', [
                    'po_id' => $po->id,
                    'procurement_item_id' => $item->id,
                    'item_unit_type_id' => $item->item_unit_type_id,
                ]);
                continue;
            }

            $itemName = trim((string) $item->item_description);
            if ($itemName === '') {
                $itemName = 'Procurement Item #' . $item->id;
            }

            $inventory = Inventory::firstOrCreate(
                [
                    'name' => $itemName,
                    'unit_id' => $unitId,
                ],
                [
                    'description' => $item->item_description,
                    'category_id' => $categoryId,
                    'min_stock_level' => 0,
                ]
            );

            $stock = InventoryStock::firstOrNew([
                'inventory_id' => $inventory->id,
                'location_id' => $po->place_of_delivery_id,
            ]);

            $currentQuantity = (float) ($stock->quantity ?? 0);
            $incomingQuantity = (float) ($item->item_quantity ?? 0);
            $newQuantity = $currentQuantity + $incomingQuantity;

            $stock->quantity = $newQuantity;
            $stock->status = $this->resolveInventoryStockStatus($newQuantity, (float) $inventory->min_stock_level);
            $stock->last_updated = now();
            $stock->save();
        }
    }

    private function rollbackCompletedItemsFromInventory(ProcurementNoaPo $po, $procurementItemIds): void
    {
        $itemIds = collect($procurementItemIds)->filter()->unique()->values();
        if ($itemIds->isEmpty()) {
            return;
        }

        $items = ProcurementItem::with('item_unit_type')
            ->whereIn('id', $itemIds)
            ->get();

        foreach ($items as $item) {
            $unitId = $this->resolveInventoryUnitIdFromProcurementItem($item);
            if (!$unitId) {
                continue;
            }

            $itemName = trim((string) $item->item_description);
            if ($itemName === '') {
                $itemName = 'Procurement Item #' . $item->id;
            }

            $inventory = Inventory::where('name', $itemName)
                ->where('unit_id', $unitId)
                ->first();

            if (!$inventory) {
                continue;
            }

            $stock = InventoryStock::where('inventory_id', $inventory->id)
                ->where('location_id', $po->place_of_delivery_id)
                ->first();

            if (!$stock) {
                continue;
            }

            $currentQuantity = (float) ($stock->quantity ?? 0);
            $outgoingQuantity = (float) ($item->item_quantity ?? 0);
            $newQuantity = max($currentQuantity - $outgoingQuantity, 0);

            $stock->quantity = $newQuantity;
            $stock->status = $this->resolveInventoryStockStatus($newQuantity, (float) $inventory->min_stock_level);
            $stock->last_updated = now();
            $stock->save();
        }
    }

    private function resolveInventoryUnitIdFromProcurementItem(ProcurementItem $item): ?int
    {
        $unitType = $item->item_unit_type;
        if (!$unitType) {
            return null;
        }

        $candidates = collect([
            $unitType->name_short ?? null,
            $unitType->name_long ?? null,
        ])->filter()->map(fn($name) => trim((string) $name))->filter()->values();

        foreach ($candidates as $candidate) {
            $unit = ListDropdown::where('classification', 'Unit')
                ->where(function ($query) use ($candidate) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower($candidate)])
                        ->orWhereRaw('LOWER(others) = ?', [strtolower($candidate)]);
                })
                ->first();

            if ($unit) {
                return (int) $unit->id;
            }
        }

        return null;
    }

    private function defaultInventoryCategoryId(): ?int
    {
        return ListDropdown::where('classification', 'Inventory Category')
            ->orderBy('id')
            ->value('id');
    }

    private function resolveInventoryStockStatus(float $quantity, float $minStockLevel): string
    {
        if ($quantity <= 0) {
            return 'out';
        }

        if ($quantity <= $minStockLevel) {
            return 'low';
        }

        return 'available';
    }


   

}
