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
            'approved_by_id' => $user->id,
            'approved_at' => now(),
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
            'status' => 'success',
        ];
    }


    public function createNOA($request, $bac_resolution , $user, $bac_reso_type = null)
    {
        $procurement = Procurement::with('quotations.items.status', 'noas.status', 'noas.items.item')
            ->findOrFail($bac_resolution->procurement_id);

        if($bac_reso_type == 'Re-award'){
            $awardedStatusId = ListStatus::getID('Awarded', 'Procurement');
            $targetProcurementItemIds = $this->resolveReawardTargetProcurementItemIds($procurement);

            $selectedAwardedItems = collect($targetProcurementItemIds)
                ->map(function ($procurementItemId) use ($procurement, $awardedStatusId) {
                    $candidates = collect();

                    foreach ($procurement->quotations as $quotation) {
                        $matchingItems = $quotation->items->filter(function ($item) use ($awardedStatusId, $procurementItemId) {
                            return (int) $item->status_id === (int) $awardedStatusId
                                && (int) $item->procurement_item_id === (int) $procurementItemId;
                        });

                        foreach ($matchingItems as $item) {
                            $candidates->push([
                                'quotation' => $quotation,
                                'item' => $item,
                            ]);
                        }
                    }

                    if ($candidates->isEmpty()) {
                        return null;
                    }

                    return $candidates->sort(function ($left, $right) {
                        $leftFreeRank = $left['item']->is_free ? 0 : 1;
                        $rightFreeRank = $right['item']->is_free ? 0 : 1;

                        if ($leftFreeRank !== $rightFreeRank) {
                            return $leftFreeRank <=> $rightFreeRank;
                        }

                        $leftPrice = (float) ($left['item']->bid_price ?? 0);
                        $rightPrice = (float) ($right['item']->bid_price ?? 0);

                        if ($leftPrice !== $rightPrice) {
                            return $leftPrice <=> $rightPrice;
                        }

                        return (int) ($left['quotation']->id ?? 0) <=> (int) ($right['quotation']->id ?? 0);
                    })->first();
                })
                ->filter();

            if ($selectedAwardedItems->isEmpty()) {
                \Log::warning('Approved re-award BAC resolution has no awarded next-supplier items.', [
                    'bac_resolution_id' => $bac_resolution->id,
                    'procurement_id' => $bac_resolution->procurement_id,
                    'target_procurement_item_ids' => $targetProcurementItemIds,
                ]);

                return;
            }

            $groupedBySupplier = $selectedAwardedItems->groupBy(function ($entry) {
                return $entry['quotation']->supplier_id ?: $entry['quotation']->id;
            });

            foreach ($groupedBySupplier as $supplierEntries) {
                $firstEntry = $supplierEntries->first();
                $quotationIds = $supplierEntries
                    ->pluck('quotation.id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $firstQuotation = $firstEntry['quotation'] ?? null;
                $awardedItemIds = $supplierEntries
                    ->pluck('item.id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (!$firstQuotation || empty($awardedItemIds)) {
                    continue;
                }

                $noa = ProcurementBacNoa::where('procurement_bac_id', $bac_resolution->id)
                    ->when($quotationIds->isNotEmpty(), function ($query) use ($quotationIds) {
                        $query->whereIn('procurement_quotation_id', $quotationIds->all());
                    })
                    ->first();

                if (!$noa) {
                    $noa = ProcurementBacNoa::create([
                        'code' => ProcurementBacNoa::generateNOANumber(),
                        'procurement_id' => $bac_resolution->procurement_id,
                        'procurement_bac_id' => $bac_resolution->id,
                        'procurement_quotation_id' => $firstQuotation->id,
                        'created_by_id' => $user->id,
                        'status_id' => ListStatus::getID('Pending','Procurement'),
                    ]);
                }

                $this->syncNOAItems($noa, $awardedItemIds);
            }

            return;
        }

        // For Award, always use fresh DB state instead of the client payload.
        $awardedStatusId = ListStatus::getID('Awarded', 'Procurement');

        $groupedBySupplier = $procurement->quotations
            ->filter(function ($quotation) use ($awardedStatusId) {
                return $quotation->items->contains(function ($item) use ($awardedStatusId) {
                    return (int) $item->status_id === (int) $awardedStatusId;
                });
            })
            ->groupBy(function ($quotation) {
                return $quotation->supplier_id ?: $quotation->id;
            });

        if ($groupedBySupplier->isEmpty()) {
            \Log::warning('Approved BAC resolution has no awarded items for NOA generation.', [
                'bac_resolution_id' => $bac_resolution->id,
                'procurement_id' => $bac_resolution->procurement_id,
            ]);

            return;
        }

        foreach ($groupedBySupplier as $supplierQuotations) {
            $quotationIds = $supplierQuotations->pluck('id')->filter()->values();
            $firstQuotation = $supplierQuotations->first();

            $awardedItemIds = $supplierQuotations
                ->flatMap(function ($quotation) use ($awardedStatusId) {
                    return $quotation->items->filter(function ($item) use ($awardedStatusId) {
                        return (int) $item->status_id === (int) $awardedStatusId;
                    });
                })
                ->pluck('id')
                ->unique()
                ->values()
                ->all();

            if (!$firstQuotation || empty($awardedItemIds)) {
                continue;
            }

            $noa = ProcurementBacNoa::where('procurement_bac_id', $bac_resolution->id)
                ->when($quotationIds->isNotEmpty(), function ($query) use ($quotationIds) {
                    $query->whereIn('procurement_quotation_id', $quotationIds);
                })
                ->first();

            if (!$noa) {
                $noa = ProcurementBacNoa::create([
                    'code' => ProcurementBacNoa::generateNOANumber(),
                    'procurement_id' => $bac_resolution->procurement_id,
                    'procurement_bac_id' => $bac_resolution->id,
                    'procurement_quotation_id' => $firstQuotation->id,
                    'created_by_id' => $user->id,
                    'status_id' => ListStatus::getID('Pending', 'Procurement'),
                ]);
            }

            $this->syncNOAItems($noa, $awardedItemIds);
        }
    }

    protected function syncNOAItems(ProcurementBacNoa $noa, array $itemIds): void
    {
        $normalizedItemIds = collect($itemIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $existingItemIds = ProcurementBacNoaItem::where('procurement_bac_noa_id', $noa->id)
            ->pluck('item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $staleItemIds = collect($existingItemIds)
            ->reject(fn ($id) => $normalizedItemIds->contains($id))
            ->values();

        if ($staleItemIds->isNotEmpty()) {
            ProcurementBacNoaItem::where('procurement_bac_noa_id', $noa->id)
                ->whereIn('item_id', $staleItemIds->all())
                ->delete();
        }

        $missingItemIds = $normalizedItemIds
            ->reject(fn ($id) => in_array($id, $existingItemIds, true));

        foreach ($missingItemIds as $itemId) {
            ProcurementBacNoaItem::create([
                'procurement_bac_noa_id' => $noa->id,
                'item_id' => $itemId,
            ]);
        }
    }

    protected function resolveReawardTargetProcurementItemIds(Procurement $procurement): array
    {
        $latestFailedNoa = $procurement->noas
            ->filter(function ($noa) {
                return in_array($noa->status?->name, ['Not Conformed', 'PO Not Conformed'], true);
            })
            ->sortByDesc(function ($noa) {
                return optional($noa->updated_at ?? $noa->created_at)->getTimestamp() ?? 0;
            })
            ->first();

        $failedNoaItemIds = collect($latestFailedNoa?->items ?? [])
            ->map(fn ($noaItem) => (int) ($noaItem->item?->procurement_item_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($failedNoaItemIds->isNotEmpty()) {
            return $failedNoaItemIds->all();
        }

        return $procurement->quotations
            ->flatMap->items
            ->filter(fn ($item) => (int) $item->status_id === (int) ListStatus::getID('Not Conformed', 'Procurement'))
            ->map(fn ($item) => (int) $item->procurement_item_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }




        



    


    

   
}
