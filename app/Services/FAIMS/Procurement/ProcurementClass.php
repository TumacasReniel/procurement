<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\Request;
use App\Models\OrgChart;
use App\Models\OrgSignatory;
use App\Models\Procurement;
use App\Models\ProcurementApp;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeGroup;
use App\Models\ProcurementCodeBudgetLog;
use App\Models\ProcurementItem;
use App\Models\ProcurementPpmpItem;
use App\Models\InventoryItem;
use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use App\Models\ListDropdown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\ListStatus;
use App\Models\ListData;
use App\Services\DropdownClass;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProcurementClass
{
    public function __construct(protected DropdownClass $dropdown)
    {
    }

    public function indexPageProps($request): array
    {
        $regionalDirector = $this->dropdown->regional_director();

        return [
            'dropdowns' => [
                'roles'  =>  Auth::user()->roles,
                'designation'  =>  Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'types' => $this->dropdown->dropdowns('Type'),
                'modes' => $this->dropdown->dropdowns('Mode'),
            ],
            'regional_director'  =>  $regionalDirector,
            'is_regional_director' => $regionalDirector && $regionalDirector['value'] == Auth::id(),
            'procurement_approval_user_ids' => $this->procurementApprovalUserIds(),
            'comment_request_id' => $request->integer('comment_request_id')
                ?: $request->integer('chat_request_id')
                ?: null,
        ];
    }

    public function createPageProps($request): array
    {
        $divisionHead = null;
        if (Auth::user()->organization && Auth::user()->organization->division_id) {
            $divisionHead = $this->dropdown->division_head(Auth::user()->organization->division_id);
        }

        return [
            'dropdowns' => [
                'divisions' => $this->dropdown->dropdowns('Division'),
                'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'reference_apps' => $this->referenceAppDropdowns(),
                'current_apps' => $this->currentAppDropdowns(),
                'app_types' => $this->dropdown->dropdowns('APP Type'),
                'procurement_codes' => $this->dropdown->procurement_codes(),
                'unit_types' => $this->dropdown->unit_types(),
                'requesters' => $this->dropdown->requesters(),
                'approvers' => $this->dropdown->approvers(),
                'regional_director' => $this->dropdown->regional_director(),
                'division_head' => $divisionHead,
            ],
            'option' => $request->option,
        ];
    }

    protected function referenceAppDropdowns(): array
    {
        $referenceApps = $this->dropdown->dropdowns('Reference APP');

        if ($referenceApps->isNotEmpty()) {
            return $referenceApps->all();
        }

        return $this->dropdown->dropdowns('APP Type')->all();
    }

    protected function currentAppDropdowns(): array
    {
        return ProcurementApp::query()
            ->with('status')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ProcurementApp $app) => [
                'value' => $app->id,
                'name' => $app->code ?: 'APP-' . $app->year,
                'code' => $app->code,
                'title' => $app->title,
                'year' => (int) $app->year,
                'status' => $app->status?->name,
            ])
            ->values()
            ->all();
    }

    public function createByCategoryPageProps($request): array
    {
        $props = $this->createPageProps($request);
        $props['dropdowns']['units'] = $this->dropdown->list_units();
        $props['dropdowns']['item_categories'] = $this->dropdown->dropdowns('Item Category');
        $props['dropdowns']['ppmp_item_categories'] = $this->approvedPpmpItemCategories();
        $props['option'] = $request->option ?: 'create_by_category';

        if ($request->filled('id')) {
            $props['procurement'] = Procurement::with(
                'division',
                'unit',
                'classification',
                'reference_app',
                'procurement_app',
                'codes',
                'items.item_unit_type',
                'items.ppmp_item.item_category',
                'items.ppmp_item.ppmp.unit',
                'approved_by.profile',
                'requested_by',
                'created_by',
                'status',
                'sub_status'
            )->findOrFail((int) $request->id);
        }

        return $props;
    }

    protected function approvedPpmpItemCategories(): array
    {
        $approvedStatusIds = $this->finalPpmpStatusIds();

        if (empty($approvedStatusIds)) {
            return [];
        }

        return ProcurementPpmpItem::query()
            ->with('item_category')
            ->whereNotNull('item_category_id')
            ->whereHas('ppmp', fn ($query) => $query->whereIn('status_id', $approvedStatusIds))
            ->get()
            ->map(fn ($item) => [
                'value' => $item->item_category_id,
                'name' => $item->item_category?->name,
            ])
            ->filter(fn ($category) => filled($category['value']) && filled($category['name']))
            ->unique(fn ($category) => (int) $category['value'])
            ->sortBy(fn ($category) => mb_strtolower($category['name']))
            ->values()
            ->all();
    }

    public function createIndexData($request)
    {
        return match ($request->option) {
            'units' => $this->dropdown->units($request->code),
            'unit_type' => $this->dropdown->unit_type($request->code),
            'title' => $this->procurement_title($request->id),
            'item_names' => $this->item_names($request->keyword),
            'ppmp_items' => $this->ppmp_items($request),
            'ppmp_category_items' => $this->ppmp_category_items($request),
            default => null,
        };
    }

    public function dashboardPageProps(): array
    {
        return [
            'dropdowns' => [
                'roles'  =>  Auth::user()->roles,
                'designation'  =>  Auth::user()->designation,
            ],
        ];
    }

    public function reportPageProps(): array
    {
        return [
            'dropdowns' => [
                'roles'  =>  Auth::user()->roles,
                'designation'  =>  Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'types' => $this->dropdown->dropdowns('Type'),
                'modes' => $this->dropdown->dropdowns('mode_of_procurement'),
            ],
            'signatories' => $this->reportSignatories(),
        ];
    }

    protected function procurementApprovalUserIds(): array
    {
        return OrgSignatory::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('oic_id', Auth::id());
            })
            ->where('is_active', 1)
            ->pluck('user_id')
            ->push(Auth::id())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function updateByOption($id, $request): array
    {
        return match ($request->option) {
            'edit' => $this->update($id, $request),
            'review' => $this->review($id, $request),
            'approve' => $this->approve($id, $request),
            'cancel' => $this->cancel($id, $request),
            default => [
                'data' => null,
                'message' => 'Invalid procurement action.',
                'info' => 'The requested procurement action is not supported.',
                'status' => false,
            ],
        };
    }

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
        $fillable = array_flip((new Procurement())->getFillable());
        $payload = array_merge(array_intersect_key($request->all(), $fillable), [
            'code' => $purchase_request_number,
            'status_id' => ListStatus::getID('Pending', 'Procurement'), //set to "Pending"
            'created_by_id' => $user->id,
        ]);

        $payload['division_id'] = $payload['division_id'] ?? $user->organization?->division_id;
        $payload['unit_id'] = $payload['unit_id'] ?? $user->organization?->unit_id;

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
            if (!empty($item['ppmp_item_id'])) {
                $ppmpItem = ProcurementPpmpItem::find($item['ppmp_item_id']);

                if ($ppmpItem) {
                    $item['item_unit_type_id'] = $ppmpItem->item_unit_type_id;
                    $item['item_name'] = $ppmpItem->item_name;
                    $item['item_unit_cost'] = $ppmpItem->item_unit_cost;
                    $item['item_quantity'] = $ppmpItem->item_quantity;
                    $item['item_description'] = $ppmpItem->item_description;
                    $item['total_cost'] = $ppmpItem->total_cost;
                }
            }

            $data = new ProcurementItem();
            $data->item_no = $index + 1;
            $data->procurement_id = $procurement_id;
            $data->ppmp_item_id = $item['ppmp_item_id'] ?? null;
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
            if (!$request->filled('procurement_app_id')) {
                $currentAppId = $this->currentAppIdForRequest($request);

                if ($currentAppId) {
                    $request->merge(['procurement_app_id' => $currentAppId]);
                }
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
        $data = Procurement::with('request')->findOrFail($id);
        $user = Auth::user();

        if ((int) $data->created_by_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'code' => 'Only the creator of this purchase request can cancel it.',
            ]);
        }

        if ($data->status?->name !== null && $data->status?->name !== 'Pending') {
            throw ValidationException::withMessages([
                'code' => 'Only pending purchase requests can be cancelled.',
            ]);
        }

        $cancelledStatusId = ListStatus::getID('Cancelled', 'Procurement');

        if (!$cancelledStatusId) {
            throw ValidationException::withMessages([
                'code' => 'The cancelled procurement status is not configured.',
            ]);
        }

        $data->status_id = $cancelledStatusId;
        $data->save();

        if ($data->request) {
            $data->request->is_completed = 1;
            $data->request->save();
        }

        $data->refresh();

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement cancelled successfully!',
            'info' => "You've successfully cancelled the purchase request.",
        ];
    }

    public function destroy($id): array
    {
        $procurement = Procurement::findOrFail($id);
        $procurement->delete();

        return [
            'data' => $id,
            'message' => 'Procurement deleted successfully!',
            'info' => "You've successfully deleted the Procurement.",
            'status' => true,
        ];
    }
    
       
    protected function updatePR($id, $request ){
        $data = Procurement::findOrFail($id);

        $fields = [
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
        ];

        if (Schema::hasColumn('procurements', 'procurement_app_id')) {
            $fields[] = 'procurement_app_id';
        }

        $data->update($request->only($fields));

        return  $data;
    }

    protected function currentAppIdForRequest($request): ?int
    {
        if (!Schema::hasTable('procurement_apps')) {
            return null;
        }

        $year = $request->filled('date')
            ? (int) date('Y', strtotime($request->date))
            : (int) now()->year;

        return ProcurementApp::query()
            ->where('year', $year)
            ->orderByDesc('id')
            ->value('id');
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

    public function ppmp_items($request): array
    {
        $unitId = (int) $request->input('unit_id');
        $procurementCodeIds = collect($request->input('procurement_code_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (!$unitId || $procurementCodeIds->isEmpty()) {
            return [];
        }

        $papEndUserUnitIds = ProcurementCode::query()
            ->whereIn('id', $procurementCodeIds)
            ->with('end_users')
            ->get()
            ->flatMap(fn ($code) => $code->end_users->pluck('end_user_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $ppmpUnitIds = $papEndUserUnitIds->isNotEmpty()
            ? $papEndUserUnitIds
            : collect([$unitId]);

        $usedPpmpItemIds = Schema::hasColumn('procurement_items', 'ppmp_item_id')
            ? ProcurementItem::query()
                ->whereNotNull('ppmp_item_id')
                ->whereHas('procurement', function ($query) use ($ppmpUnitIds) {
                    $query
                        ->whereIn('unit_id', $ppmpUnitIds)
                        ->where('code', 'not like', 'PPMP-%')
                        ->whereDoesntHave('status', function ($statusQuery) {
                            $statusQuery->where('name', 'Cancelled');
                        });
                })
                ->pluck('ppmp_item_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
            : collect();

        $usedItemSignatures = ProcurementItem::query()
            ->whereHas('procurement', function ($query) use ($ppmpUnitIds) {
                $query
                    ->whereIn('unit_id', $ppmpUnitIds)
                    ->where('code', 'not like', 'PPMP-%')
                    ->whereDoesntHave('status', function ($statusQuery) {
                        $statusQuery->where('name', 'Cancelled');
                    });
            })
            ->get(['item_name', 'item_description', 'item_quantity', 'item_unit_type_id', 'item_unit_cost', 'total_cost'])
            ->map(fn ($item) => $this->ppmp_item_signature($item))
            ->filter()
            ->unique()
            ->values();

        return ProcurementPpmpItem::query()
            ->with([
                'item_unit_type',
                'ppmp.reference_app',
                'ppmp.codes.procurement_code',
            ])
            ->when($usedPpmpItemIds->isNotEmpty(), function ($query) use ($usedPpmpItemIds) {
                $query->whereNotIn('id', $usedPpmpItemIds);
            })
            ->whereHas('ppmp', function ($query) use ($ppmpUnitIds, $procurementCodeIds) {
                $query->whereIn('unit_id', $ppmpUnitIds)
                    ->where(function ($ppmpQuery) use ($procurementCodeIds) {
                        $ppmpQuery
                            ->whereHas('codes', function ($codeQuery) use ($procurementCodeIds) {
                                $codeQuery->whereIn('procurement_code_id', $procurementCodeIds);
                            })
                            ->orDoesntHave('codes');
                    });
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->reject(fn ($item) => $usedItemSignatures->contains($this->ppmp_item_signature($item)))
            ->map(function ($item) {
                $procurement = $item->ppmp;
                $year = $procurement?->date ? date('Y', strtotime($procurement->date)) : date('Y');
                $ppmpNo = $procurement
                    ? 'PPMP-' . $year . '-' . str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT)
                    : null;
                $quantity = (float) ($item->item_quantity ?? 0);
                $unitName = $quantity > 1
                    ? ($item->item_unit_type?->name_long ?? $item->item_unit_type?->name_short)
                    : ($item->item_unit_type?->name_short ?? $item->item_unit_type?->name_long);

                return [
                    'value' => $item->id,
                    'label' => trim(($ppmpNo ? "{$ppmpNo} - " : '') . ($item->item_name ?: 'PPMP Item')),
                    'ppmp_id' => $procurement?->id,
                    'ppmp_no' => $ppmpNo,
                    'pr_no' => $procurement?->code,
                    'plan_name' => $procurement?->reference_app?->name ?: 'PPMP',
                    'pap_code_ids' => $procurement?->codes
                        ? $procurement->codes->pluck('procurement_code_id')->map(fn ($id) => (int) $id)->values()
                        : [],
                    'item_name' => $item->item_name,
                    'item_description' => $item->item_description,
                    'item_quantity' => $item->item_quantity,
                    'item_unit_type_id' => $item->item_unit_type_id,
                    'item_unit_type' => $item->item_unit_type,
                    'item_unit_cost' => (float) $item->item_unit_cost,
                    'total_cost' => (float) $item->total_cost,
                    'quantity_label' => trim($item->item_quantity . ' ' . ($unitName ?: '')),
                ];
            })
            ->values()
            ->all();
    }

    public function ppmp_category_items($request): array
    {
        $categoryId = (int) $request->input('item_category_id');
        $fundClusterId = (int) $request->input('fund_cluster_id');
        $approvedStatusIds = $this->finalPpmpStatusIds();
        if (!$categoryId || !$fundClusterId || empty($approvedStatusIds)) {
            return [];
        }

        $usedPpmpItemIds = Schema::hasColumn('procurement_items', 'ppmp_item_id')
            ? ProcurementItem::query()
                ->whereNotNull('ppmp_item_id')
                ->whereHas('procurement', function ($query) {
                    $query
                        ->where('code', 'not like', 'PPMP-%')
                        ->whereDoesntHave('status', function ($statusQuery) {
                            $statusQuery->where('name', 'Cancelled');
                        });
                })
                ->pluck('ppmp_item_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
            : collect();

        return ProcurementPpmpItem::query()
            ->with([
                'item_unit_type',
                'item_category',
                'ppmp.unit',
                'ppmp.reference_app',
                'ppmp.codes.procurement_code',
            ])
            ->where('item_category_id', $categoryId)
            ->when($usedPpmpItemIds->isNotEmpty(), function ($query) use ($usedPpmpItemIds) {
                $query->whereNotIn('id', $usedPpmpItemIds);
            })
            ->whereHas('ppmp', function ($query) use ($approvedStatusIds, $fundClusterId) {
                $query
                    ->whereIn('status_id', $approvedStatusIds)
                    ->where('fund_cluster_id', $fundClusterId);
            })
            ->latest('id')
            ->get()
            ->map(function ($item) {
                $procurement = $item->ppmp;
                $year = $procurement?->date ? date('Y', strtotime($procurement->date)) : date('Y');
                $ppmpNo = $procurement
                    ? 'PPMP-' . $year . '-' . str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT)
                    : null;
                $quantity = (float) ($item->item_quantity ?? 0);
                $unitName = $quantity > 1
                    ? ($item->item_unit_type?->name_long ?? $item->item_unit_type?->name_short)
                    : ($item->item_unit_type?->name_short ?? $item->item_unit_type?->name_long);

                return [
                    'value' => $item->id,
                    'label' => trim(($ppmpNo ? "{$ppmpNo} - " : '') . ($item->item_name ?: 'PPMP Item')),
                    'ppmp_id' => $procurement?->id,
                    'ppmp_no' => $ppmpNo,
                    'pr_no' => $procurement?->code,
                    'unit_id' => $procurement?->unit_id,
                    'unit_name' => $procurement?->unit?->name,
                    'item_category_id' => $item->item_category_id,
                    'item_category' => $item->item_category?->name,
                    'plan_name' => $procurement?->reference_app?->name ?: 'PPMP',
                    'pap_code_ids' => $procurement?->codes
                        ? $procurement->codes->pluck('procurement_code_id')->map(fn ($id) => (int) $id)->values()
                        : [],
                    'item_name' => $item->item_name,
                    'item_description' => $item->item_description,
                    'item_quantity' => $item->item_quantity,
                    'item_unit_type_id' => $item->item_unit_type_id,
                    'item_unit_type' => $item->item_unit_type,
                    'item_unit_cost' => (float) $item->item_unit_cost,
                    'total_cost' => (float) $item->total_cost,
                    'quantity_label' => trim($item->item_quantity . ' ' . ($unitName ?: '')),
                ];
            })
            ->values()
            ->all();
    }

    protected function finalPpmpStatusIds(): array
    {
        return collect([
            ListStatus::getID('Reviewed', 'Procurement'),
            ListStatus::getID('Approved', 'Procurement'),
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function ppmp_item_signature($item): string
    {
        return implode('|', [
            mb_strtolower(trim((string) ($item->item_name ?? ''))),
            trim(strip_tags((string) ($item->item_description ?? ''))),
            (string) (float) ($item->item_quantity ?? 0),
            (string) (int) ($item->item_unit_type_id ?? 0),
            number_format((float) ($item->item_unit_cost ?? 0), 2, '.', ''),
            number_format((float) ($item->total_cost ?? 0), 2, '.', ''),
        ]);
    }

    protected function reportSignatories(): array
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

        $assistantRegionalDirector = OrgChart::with('user.profile', 'oic.profile', 'designation', 'assigned')
            ->where('designation_id', ListDropdown::getID('Assistant Regional Director', 'Designation'))
            ->whereHas('assigned', function ($query) {
                $query->where('others', 'FASS')
                    ->orWhere('name', 'like', '%Finance and Administrative Support Services%');
            })
            ->orderByDesc('is_active')
            ->orderBy('order')
            ->first();
        $notedByUser = $assistantRegionalDirector?->is_oic
            ? ($assistantRegionalDirector?->oic ?: $assistantRegionalDirector?->user)
            : ($assistantRegionalDirector?->user ?: $assistantRegionalDirector?->oic);
        $notedByDesignation = $assistantRegionalDirector?->is_oic ? 'OIC ARD-FASS' : 'ARD-FASS';

        return [
            'prepared_by' => array_slice($procurementStaff, 0, 2),
            'supply_officer' => $supplyOfficer ? [
                'name' => strtoupper($supplyOfficer->profile?->full_name ?? ('USER #' . $supplyOfficer->id)),
                'role' => 'Supply Officer',
            ] : null,
            'noted_by' => $notedByUser ? [
                'name' => strtoupper($notedByUser->profile?->full_name ?? ''),
                'designation' => $notedByDesignation,
            ] : null,
        ];
    }

}
