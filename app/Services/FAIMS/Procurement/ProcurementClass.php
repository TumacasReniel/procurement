<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\Request;
use App\Models\Procurement;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeGroup;
use App\Models\ProcurementCodeBudgetLog;
use App\Models\ProcurementItem;
use App\Models\InventoryItem;
use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\ListStatus;
use App\Models\ListData;

class ProcurementClass
{
    public function save($request){
        $data = Request::create([
            'code' => $this->generateCode(),
            'type_id' => ListData::getID('Procurement'),
            'status_id' => ListStatus::getID('Pending','Procurement'),
            'user_id' => \Auth::user()->id
        ]);
                                         
        // Save Procurement
        $procurement = $this->saveProcurement($request, $data);

        // Save Procurement Items 
        $this->saveProcurementItems($request, $procurement->id);

        return [
            'data' => new ProcurementResource($procurement),
            'message' => 'Procurement creation was successful!', 
            'info' => "You've successfully created new Procurement.",
        ];
    }

    public function saveProcurement($request, $data){
        $user = Auth::user();
        $purchase_request_number = Procurement::generateProcurementNumber();
        $payload = array_merge($request->all(), [
            'code' => $purchase_request_number,
            'status_id' => ListStatus::getID('Pending', 'Procurement'), //set to "Pending"
            'created_by_id' => $user->id,
        ]);

        // Handle schema drift safely for older DBs that may not yet have request_id.
        if (Schema::hasColumn('procurements', 'request_id')) {
            $payload['request_id'] = $data->id;
        }

        $procurement = Procurement::create($payload);

        if (!empty($request->procurement_code_ids) && is_array($request->procurement_code_ids)) {
            $this->syncProcurementCodes($procurement->id, $request->procurement_code_ids);
        }

      
        return $procurement;
    }
    

    protected function saveProcurementItems($request ,$procurement_id ){
    
        foreach ($request->items as $index => $item) {
            $data = new ProcurementItem();
            $data->item_no = $index + 1;
            $data->procurement_id = $procurement_id;
            $data->item_unit_type_id =  $item['item_unit_type_id'];
            $data->item_name = $item['item_name'] ?? null;
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

        // update Procurement PAP Codes
        $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

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
            if (!$request->filled('classification_id')) {
                $procurement = Procurement::findOrFail($id);

                return [
                    'data' => new ProcurementResource($procurement),
                    'message' => 'Procurement classification is required.',
                    'info' => 'Please select whether this PR is for Goods and Services, Infrastructure Projects, or Consulting Services before reviewing.',
                    'status' => 'warning',
                ];
            }

            // update Procurement
            $data = $this->updatePR($id , $request);

            // update Procurement PAP Codes
            $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

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

        // update Procurement PAP Codes
        $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

        // update Procurement Item Details       
        $this->updatePRItems($id, $request);

        $this->applyApprovedBudgetDeductions($id);

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
            'date',
            'purpose',
            'title',
            'division_id',
            'unit_id',
            'fund_cluster_id',
            'classification_id',
            'reference_app_id',
            'requested_by_id',
            'approved_by_id'
        )));

        return  $data;
    }

    protected function syncProcurementCodes($procurement_id, $procurementCodeIds = []): void
    {
        ProcurementCodeGroup::where('procurement_id', $procurement_id)->delete();

        foreach (collect($procurementCodeIds)->filter()->unique() as $procurement_code_id) {
            ProcurementCodeGroup::create([
                'procurement_code_id' => $procurement_code_id,
                'procurement_id' => $procurement_id,
            ]);
        }
    }

    protected function updatePRItems($procurement_id, $request ){

        // Delete existing items for the procurement
        ProcurementItem::where('procurement_id', $procurement_id)->delete();

        // Re-save the updated items
        $this->saveProcurementItems($request, $procurement_id);
    }

    protected function applyApprovedBudgetDeductions(int $procurementId): void
    {
        $existingLogs = ProcurementCodeBudgetLog::query()
            ->where('procurement_id', $procurementId)
            ->where('type', 'approval_deduction')
            ->get();

        $procurement = Procurement::with(['codes'])
            ->findOrFail($procurementId);

        $procurementCodeIds = $procurement->codes
            ->pluck('procurement_code_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($procurementCodeIds)) {
            return;
        }

        $remainingDeductionCents = $this->amountToCents(
            ProcurementItem::where('procurement_id', $procurementId)->sum('total_cost')
        );

        $alreadyDeductedCents = $this->amountToCents($existingLogs->sum('amount'));
        $remainingDeductionCents -= $alreadyDeductedCents;

        if ($remainingDeductionCents <= 0) {
            return;
        }

        $alreadyLoggedCodeIds = $existingLogs
            ->pluck('procurement_code_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $budgetCodes = ProcurementCode::query()
            ->whereIn('id', $procurementCodeIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lastIndex = count($procurementCodeIds) - 1;

        foreach ($procurementCodeIds as $index => $procurementCodeId) {
            if ($remainingDeductionCents <= 0) {
                break;
            }

            $budgetCode = $budgetCodes->get($procurementCodeId);

            if (!$budgetCode) {
                continue;
            }

            if (in_array($procurementCodeId, $alreadyLoggedCodeIds, true)) {
                continue;
            }

            $balanceBeforeCents = $this->amountToCents(
                $budgetCode->remaining_budget ?? $budgetCode->allocated_budget
            );

            $amountCents = $index === $lastIndex
                ? $remainingDeductionCents
                : min($remainingDeductionCents, max($balanceBeforeCents, 0));

            if ($amountCents <= 0 && $index !== $lastIndex) {
                continue;
            }

            $balanceAfterCents = $balanceBeforeCents - $amountCents;

            ProcurementCodeBudgetLog::create([
                'procurement_code_id' => $budgetCode->id,
                'procurement_id' => $procurementId,
                'processed_by_id' => Auth::id(),
                'type' => 'approval_deduction',
                'amount' => $this->centsToAmount($amountCents),
                'balance_before' => $this->centsToAmount($balanceBeforeCents),
                'balance_after' => $this->centsToAmount($balanceAfterCents),
                'description' => 'Budget deducted after approving procurement request ' . $procurement->code,
            ]);

            $budgetCode->remaining_budget = $this->centsToAmount($balanceAfterCents);
            $budgetCode->save();

            $remainingDeductionCents -= $amountCents;
        }
    }

    protected function amountToCents($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    protected function centsToAmount(int $amountInCents): float
    {
        return round($amountInCents / 100, 2);
    }

    

    public function procurement_title($code_id)
    {  
        $data = ProcurementCode::findOrFail($code_id);
        return $data->title;
    }

    public function item_names($keyword = null)
    {
        $keyword = trim((string) $keyword);
        $limit = 20;
        $names = collect();

        if (Schema::hasTable('procurement_items')) {
            $names = $names->merge(
                ProcurementItem::query()
                    ->select('item_name')
                    ->whereNotNull('item_name')
                    ->where('item_name', '!=', '')
                    ->when($keyword !== '', function ($query) use ($keyword) {
                        $query->where('item_name', 'like', '%' . $keyword . '%');
                    })
                    ->distinct()
                    ->orderBy('item_name')
                    ->limit($limit)
                    ->pluck('item_name')
            );
        }

        if (Schema::hasTable('inventory_items')) {
            $names = $names->merge(
                InventoryItem::query()
                    ->select('name')
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->when($keyword !== '', function ($query) use ($keyword) {
                        $query->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->distinct()
                    ->orderBy('name')
                    ->limit($limit)
                    ->pluck('name')
            );
        }

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->sortBy(fn ($name) => mb_strtolower($name))
            ->values()
            ->take($limit)
            ->all();
    }

    
  
   
}
