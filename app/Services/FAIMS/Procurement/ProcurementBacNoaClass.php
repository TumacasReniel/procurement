<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\ProcurementBac;
use App\Models\Procurement;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementBacNoaItem;
use App\Models\ProcurementQuotationItem;
use App\Http\Resources\FAIMS\Procurement\ProcurementBacNoaResource;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ListStatus;
use Spatie\Activitylog\Models\Activity;


class ProcurementBacNoaClass
{
    public function lists($request){
        $this->backfillMissingNOAs($request->procurement_id);

        $data = ProcurementBacNoaResource::collection(
            ProcurementBacNoa::with(
                'procurement_bac.procurement',
                'procurement_quotation.supplier.address',
                'procurement_quotation.supplier.conformes',
                'items.item.item.item_unit_type',
                'status'
            )
            ->when($request->procurement_id, function ($query, $procurement_id) {
                $query->where('procurement_id', $procurement_id);
            })
            ->when($request->bac_resolution_id, function ($query, $bac_resolution_id) {
                $query->where('procurement_bac_id', $bac_resolution_id);
            })
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('code', 'LIKE', "%{$keyword}%")
                      ->orWhereHas('procurement', function ($q) use ($keyword) {
                        $q->where('code', 'LIKE', "%{$keyword}%");
                      })
                      ->orWhereHas('procurement_quotation.supplier', function ($q) use ($keyword) {
                        $q->where('name', 'LIKE', "%{$keyword}%");
                      });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status_id', $status);
            })
            ->orderBy('created_at','DESC')
            ->paginate($request->count)
        );

        return $data;
    }

    protected function backfillMissingNOAs($procurementId = null): void
    {
        if (!$procurementId) {
            return;
        }

        $approvedStatusId = ListStatus::getID('Approved', 'Procurement');
        if (!$approvedStatusId) {
            return;
        }

        $missingApprovedBacs = ProcurementBac::query()
            ->where('procurement_id', $procurementId)
            ->where('status_id', $approvedStatusId)
            ->where('type', '!=', 'Rebid')
            ->doesntHave('notice_of_awards')
            ->get();

        if ($missingApprovedBacs->isEmpty()) {
            return;
        }

        $bacService = app(ProcurementBACClass::class);
        $actor = Auth::user();

        foreach ($missingApprovedBacs as $bacResolution) {
            $user = null;

            if ($bacResolution->approved_by_id) {
                $user = User::find($bacResolution->approved_by_id);
            }

            if (!$user && $bacResolution->created_by_id) {
                $user = User::find($bacResolution->created_by_id);
            }

            if (!$user) {
                $user = $actor;
            }

            if (!$user) {
                \Log::warning('Skipping NOA backfill because no user context could be resolved.', [
                    'bac_resolution_id' => $bacResolution->id,
                    'procurement_id' => $procurementId,
                ]);
                continue;
            }

            $bacService->createNOA(request(), $bacResolution, $user, $bacResolution->type);
        }
    }

    public function update($id, $request)
    {
        $user = Auth::user();
        $noa = ProcurementBacNoa::with('procurement_bac.procurement', 'procurement_quotation.supplier', 'status')->findOrFail($id);
        $body = $request->input('body', $request->input('remarks'));

        if ($noa->status?->name !== 'Pending') {
            return [
                'data' => new ProcurementBacNoaResource($noa->fresh([
                    'procurement_bac.procurement',
                    'procurement_quotation.supplier.address',
                    'procurement_quotation.supplier.conformes',
                    'items.item.item.item_unit_type',
                    'status',
                ])),
                'message' => 'NOA can no longer be edited.',
                'info' => 'Only NOAs with Pending status can be edited.',
                'status' => 'warning',
            ];
        }

        $noa->update([
            'remarks' => $body,
        ]);

        activity()->performedOn($noa)->causedBy($user)->log('NOA content updated');

        return [
            'data' => new ProcurementBacNoaResource($noa->fresh([
                'procurement_bac.procurement',
                'procurement_quotation.supplier.address',
                'procurement_quotation.supplier.conformes',
                'items.item.item.item_unit_type',
                'status',
            ])),
            'message' => 'NOA updated successfully!',
            'info' => "You've successfully updated the NOA content.",
            'status' => 'success',
        ];
    }

       
       
    public function updateStatus($id, $request)
    {
        $user = Auth::user();
        $noa = ProcurementBacNoa::with('procurement_bac.procurement' , 'status')->findOrFail($id);

        // Get current status name
        $statusPayload = $request->input('status');
        $currentStatusName = is_array($statusPayload)
            ? ($statusPayload['name'] ?? null)
            : (is_object($statusPayload) ? ($statusPayload->name ?? null) : null);

        if (!$currentStatusName) {
            return [
                'data' => new ProcurementBacNoaResource($noa),
                'message' => 'Missing status payload.',
                'info' => 'No status update was applied.',
                'status' => 'warning',
            ];
        }
   
        // Update NOA status FIRST
        if($currentStatusName == "Pending"){
            $noa->update([
                'served_at' => now(),
                'conformed_at' => null,
                'status_id' => ListStatus::getID('Served to Supplier','Procurement'), // set status to "Served to Supplier"
            ]);
            activity()->performedOn($noa)->causedBy($user)->log('NOA status updated to Served to Supplier');
        }
        elseif($currentStatusName == "Served to Supplier"){
            $noa->update([
                'conformed_at' => now(),
                'status_id' => ListStatus::getID('Conformed','Procurement'), // set status to "Conformed"
            ]);
            activity()->performedOn($noa)->causedBy($user)->log('NOA status updated to Conformed');
        }
        elseif($currentStatusName == "Conformed"){
            $noa->update([
                'status_id' => ListStatus::getID('Delivered/For Inspection','Procurement'), // set status to "Delivered/For Inspection"
            ]);
            activity()->performedOn($noa)->causedBy($user)->log('NOA status updated to Delivered/For Inspection');
        }

        // Refresh NOA to get the new status
        $noa->refresh();
     
        // Refresh the procurement_bac relationship with the new NOA status
        $noa->load('procurement_bac.notice_of_awards');
        
        $procurement = $noa->procurement_bac->procurement;
        $current_pr_status = $procurement->status_id;
        $current_sub_status = $procurement->sub_status_id;

        // if current_pr_status "Re-award" or  "Rebid"
        if($current_pr_status == ListStatus::getID('Re-award','Procurement')  || $current_pr_status == ListStatus::getID('Rebid','Procurement')){

            
            $updated_pr_substatus = $noa->procurement_bac->overall_substatus($current_sub_status);
       
            // update Procurement Request Status (only if we get a valid status)
            if($updated_pr_substatus !== null && is_numeric($updated_pr_substatus)){
                $procurement->update([
                    'sub_status_id' =>  $updated_pr_substatus,
                ]);
            } else {
                // Log the error but don't fail - keep the current status
                \Log::warning('Invalid sub_status returned from overall_substatus', [
                    'updated_pr_substatus' => $updated_pr_substatus,
                    'current_sub_status' => $current_sub_status,
                    'procurement_id' => $procurement->id,
                    'noa_id' => $noa->id
                ]);
            }

        }
        else{
            $updated_pr_status = $noa->procurement_bac->overall_status($current_pr_status);
     
            // update Procurement Request Status (only if we get a valid status)
            if($updated_pr_status !== null && is_numeric($updated_pr_status)){
                $procurement->update([
                    'status_id' =>  $updated_pr_status,
                ]);
            } else {
                // Log the error but don't fail - keep the current status
                \Log::warning('Invalid status returned from overall_status', [
                    'updated_pr_status' => $updated_pr_status,
                    'current_pr_status' => $current_pr_status,
                    'procurement_id' => $procurement->id,
                    'noa_id' => $noa->id
                ]);
            }
        }

      
        
        return [
            'data' =>new ProcurementBacNoaResource($noa),
            'message' => 'NOA Status updated successfully!', 
            'info' => "You've successfully updated NOA Status.",
            'status' => 'success',
        ];
    }


    public function notConformed($id, $request)
    { 
        $user = Auth::user();
        $noa = ProcurementBacNoa::with('procurement_bac.procurement')->findOrFail($id);

        $noa->update([
            'conformed_at' => null,
            'status_id' => ListStatus::getID('Not Conformed','Procurement'), // set status to "Not Conformed"
            'updated_by_id' => $user->id,
        ]);

        activity()->performedOn($noa)->causedBy($user)->log('NOA status updated to Not Conformed and '. $user->profile->fullname . ' commented '. $request->comment);

        $noa->comments()->create([
            'content' => $request->comment,
            'user_id' => $user->id,
        ]);
        
        $procurement = $noa->procurement_bac->procurement;
        $current_pr_status = $noa->procurement_bac->procurement->status_id;

        // Determine if rebid or reaward
        $updated_pr_status = $noa->procurement_bac->determine_re_award_or_rebid();
    
        // if updated status is "Re-award" or "Rebid"
        if($updated_pr_status  == ListStatus::getID('Re-award','Procurement') || $updated_pr_status  == ListStatus::getID('Rebid','Procurement')){
            if($updated_pr_status  == ListStatus::getID('Re-award','Procurement')){
                $procurement->update([
                    'sub_status_id' => null,
                    'reawarded_count' =>  $procurement->reawarded_count + 1,
                ]);
            }
            else if($updated_pr_status  == ListStatus::getID('Rebid','Procurement')){
                $procurement->update([
                    'sub_status_id' => null,
                    'rebidded_count' =>  $procurement->rebidded_count + 1,
                ]);
            }
            // --- update the old BAC Resolution status to "NOA Not Conformed" -----
            $noa->procurement_bac->update([
                'status_id' => ListStatus::getID('Not Conformed','Procurement'),
            ]);

        }

        // update Procurement Request Status
        $procurement->update([
            'status_id' => $updated_pr_status,
            'updated_by_id' => $user->id,
        ]); 

        // update Quotation items status to "Not Conformed" only items which is related to the noa
        $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $noa->id)->get();
        foreach ($noa_items as $noa_item) {
            $quotation_item = $noa_item->item;
            $quotation_item->update([
                'status_id' => ListStatus::getID('Not Conformed','Procurement')
            ]);
        }

        // Update the next item to be awarded in other quotations
        $procurement = $noa->procurement_bac->procurement;
        $other_quotations = $procurement->quotations->where('id', '!=', $noa->procurement_quotation_id);
        $available_items = $other_quotations->flatMap->items->filter(fn($item) =>
            $item->status_id == ListStatus::getID('Available for Re-award','Procurement') &&
            ($item->is_free || (float) $item->bid_price > 0)
        );
        if ($available_items->isNotEmpty()) {
            $next_item = $available_items->sortBy('bid_price')->first();
            $next_item->update(['status_id' => ListStatus::getID('Awarded','Procurement')]);
        }

    
        return [
            'data' =>new ProcurementBacNoaResource($noa),
            'message' => 'BAC Resolution Status updated successfully!', 
            'info' => "You've successfully updated BAC Resolution Status.",
            'status' => 'success',
        ];
    }

    public function revertStatus($id, $request)
    {
        $user = Auth::user();
        $noa = ProcurementBacNoa::with('procurement_bac.procurement', 'status')->findOrFail($id);

        $currentStatusName = $noa->status?->name;
        $revertedTo = null;

        if ($currentStatusName === 'Delivered/For Inspection') {
            $revertedTo = 'Conformed';
        } elseif ($currentStatusName === 'Conformed') {
            $revertedTo = 'Served to Supplier';
        } elseif ($currentStatusName === 'Served to Supplier') {
            $revertedTo = 'Pending';
        }

        if (!$revertedTo) {
            return [
                'data' => new ProcurementBacNoaResource($noa),
                'message' => 'NOA status cannot be reverted from the current step.',
                'info' => 'Nothing changed.',
                'status' => 'warning',
            ];
        }

        $revertData = [
            'status_id' => ListStatus::getID($revertedTo, 'Procurement'),
        ];

        if ($revertedTo === 'Served to Supplier') {
            $revertData['conformed_at'] = null;
        } elseif ($revertedTo === 'Pending') {
            $revertData['served_at'] = null;
            $revertData['conformed_at'] = null;
        }

        $noa->update($revertData);
        activity()->performedOn($noa)->causedBy($user)->log("NOA status reverted to {$revertedTo}");

        $noa->refresh();
        $noa->load('procurement_bac.notice_of_awards');

        $procurement = $noa->procurement_bac->procurement;
        $current_pr_status = $procurement->status_id;
        $current_sub_status = $procurement->sub_status_id;

        if (
            $current_pr_status == ListStatus::getID('Re-award', 'Procurement') ||
            $current_pr_status == ListStatus::getID('Rebid', 'Procurement')
        ) {
            $updated_pr_substatus = $noa->procurement_bac->overall_substatus($current_sub_status);
            if ($updated_pr_substatus !== null && is_numeric($updated_pr_substatus)) {
                $procurement->update([
                    'sub_status_id' => $updated_pr_substatus,
                ]);
            }
        } else {
            $updated_pr_status = $noa->procurement_bac->overall_status($current_pr_status);
            if ($updated_pr_status !== null && is_numeric($updated_pr_status)) {
                $procurement->update([
                    'status_id' => $updated_pr_status,
                ]);
            }
        }

        return [
            'data' => new ProcurementBacNoaResource($noa),
            'message' => 'NOA Status reverted successfully!',
            'info' => "You've successfully reverted NOA Status.",
            'status' => 'success',
        ];
    }

    


    


   
}
