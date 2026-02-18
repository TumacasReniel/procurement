<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\Request;
use App\Models\Procurement;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeGroup;
use App\Models\ProcurementItem;
use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ListStatus;

class ProcurementClass
{
    public function save($request){
        $data = Request::create([
            'code' => $this->generateCode(),
            'type_id' => 175,
            'status_id' => ListStatus::getID('Pending','Procurement'),
            'user_id' => \Auth::user()->id
        ]);
                                         
        // Save Procurement
        $procurement = $this->saveProcurement($request);

        // Save Procurement Items 
        $this->saveProcurementItems($request, $procurement->id);

        return [
            'data' => new ProcurementResource($procurement),
            'message' => 'Procurement creation was successful!', 
            'info' => "You've successfully created new Procurement.",
        ];
    }

    public function saveProcurement($request){
        $user = Auth::user();
        $purchase_request_number = Procurement::generateProcurementNumber();
        $procurement = Procurement::create(array_merge($request->all(), [ 
            'code' => $purchase_request_number,
            'status_id' => ListStatus::getID('Pending','Procurement'), //set to "Pending"
            'created_by_id' => $user->id, 
         ] )); 

        if (!empty($request->procurement_code_ids) && is_array($request->procurement_code_ids)) {
            // Save PAP codes
            foreach ($request->procurement_code_ids as $procurement_code_id) {
                $procurement_code_group = new ProcurementCodeGroup();
                $procurement_code_group->procurement_code_id = $procurement_code_id;
                $procurement_code_group->procurement_id = $procurement->id;
                $procurement_code_group->save();
            }
        }
        return $procurement;
    }
    

    protected function saveProcurementItems($request ,$procurement_id ){
    
        foreach ($request->items as $index => $item) {
            $data = new ProcurementItem();
            $data->item_no = $index + 1;
            $data->procurement_id = $procurement_id;
            $data->item_unit_type_id =  $item['item_unit_type_id'];
            $data->item_unit_cost = $item['item_unit_cost'];
            $data->item_quantity = $item['item_quantity'];
            $data->item_description = $item['item_description'];
            $data->total_cost = $item['total_cost'];
            $data->status_id = ListStatus::getID('Pending','Procurement');
            $data->save();
        }

    }

    
    private function generateCode()
    {
        return \DB::transaction(function () {
            $latest = Request::lockForUpdate()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->orderByDesc('id')
                ->first();

            $count = $latest
                ? (int) substr($latest->code, -4) + 1
                : 1;

            $code = 'REQUEST-' . now()->format('mY') . '-PR-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            return $code;
        });
    }
    
    public function update($id , $request)
    {
        // update Procurement
        $data = $this->updatePR($id , $request);

        // update Procurement Item Details
        $this->updatePRItems($id , $request);


        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement updated successfuly!',
            'info' => "You've successfully updated the Procurement.",
        ];
    }
    
   
    public function review($id, $request)
    {
        $user = Auth::user();
        Log::info('Procurement review started', [
            'procurement_id' => $id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'review_procurement'
        ]);

        try {
            // update Procurement
            $data = $this->updatePR($id , $request);

            // update Procurement Item Details
            $this->updatePRItems($id, $request);

            //  update status to reviewed
            $data->status_id  = ListStatus::getID('Reviewed','Procurement');

            $data->update();

            Log::info('Procurement reviewed successfully', [
                'procurement_id' => $id,
                'procurement_code' => $data->code,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'new_status_id' => ListStatus::getID('Reviewed','Procurement')
            ]);

            return [
                'data' => new ProcurementResource($data),
                'message' => 'Procurement reviewed successfuly!',
                'info' => "You've successfully updated the Procurement.",
            ];
        } catch (\Exception $e) {
            Log::error('Procurement review failed', [
                'procurement_id' => $id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function approve($id, $request)
    {
        // update Procurement
        $data = $this->updatePR($id , $request);

        // update Procurement Item Details       
        $this->updatePRItems($id, $request);

        //  update status to approved
        $data->status_id  = ListStatus::getID('Approved','Procurement');

        $data->update();

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement reviewed successfuly!', 
            'info' => "You've successfully updated the Procurement.",
        ];
    }


    public function cancel($id, $request)
    {
        $data = Procurement::findOrFail($id);

        //  update status to approved
        $data->status_id  = ListStatus::getID('Cancelled','Procurement');
        $data->update();

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement reviewed successfuly!', 
            'info' => "You've successfully updated the Procurement.",
        ];
    }
    
       
    protected function updatePR($id, $request ){
        $data = Procurement::findOrFail($id);

        $data->update(array_merge($request->only(
            'purpose',
            'title',
            'division_id',
            'unit_id',
            'fund_cluster_id',
            'requested_by_id',
            'approved_by_id'
        )));

        return  $data;
    }

    protected function updatePRItems($procurement_id, $request ){

        // Delete existing items for the procurement
        ProcurementItem::where('procurement_id', $procurement_id)->delete();

        // Re-save the updated items
        $this->saveProcurementItems($request, $procurement_id);
    }

    

     public function procurement_title($code_id)
    {  
        $data = ProcurementCode::findOrFail($code_id);
        return $data->title;
    }

    
  
   
}
