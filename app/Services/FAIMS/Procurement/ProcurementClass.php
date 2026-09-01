<?php

namespace App\Services\FAIMS\Procurement;

use App\Events\ProcurementRequestChanged;
use App\Models\Request;
use App\Models\OrgChart;
use App\Models\OrgSignatory;
use App\Models\Procurement;
use App\Models\ProcurementApp;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeGroup;
use App\Models\ProcurementCodeBudgetLog;
use App\Models\ProcurementItem;
use App\Models\ProcurementPpmp;
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
    protected const PLAN_NAME_APP = 'Annual Procurement Plan';

    public function __construct(
        protected DropdownClass $dropdown,
        protected ProcurementGate $gate,
    ) {
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
                'item_categories' => $this->dropdown->dropdowns('Item Category'),
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
        $appTypeId = ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type');
        $approvedStatusId = ListStatus::getID('Approved', 'Procurement');

        // Only expose Approved APPs — PRs cannot be created against a pending or under-review APP.
        // Ordered by year desc then version desc so the latest approved version per year is first.
        return ProcurementApp::query()
            ->with('status')
            ->when($appTypeId, fn ($query) => $query->where('app_type_id', $appTypeId))
            ->when($approvedStatusId, fn ($query) => $query->where('status_id', $approvedStatusId))
            ->orderByDesc('year')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ProcurementApp $app) => [
                'value' => $app->id,
                'name' => $app->code ?: 'APP-' . $app->year,
                'code' => $app->code,
                'title' => $app->title,
                'year' => (int) $app->year,
                'version' => (int) ($app->version ?? 1),
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

        $approvedAppStatusId = ListStatus::getID('Approved', 'Procurement');

        return ProcurementPpmpItem::query()
            ->with('item_category')
            ->whereNotNull('item_category_id')
            ->whereHas('ppmp', function ($query) use ($approvedStatusIds, $approvedAppStatusId) {
                $query
                    ->whereIn('status_id', $approvedStatusIds)
                    ->whereHas('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_APP));

                if (Schema::hasColumn('procurement_ppmps', 'procurement_app_id')) {
                    $query->whereNotNull('procurement_app_id')
                        ->when($approvedAppStatusId, fn ($q) => $q->whereHas('procurement_app', function ($a) use ($approvedAppStatusId) {
                            $a->where('status_id', $approvedAppStatusId);
                            if (Schema::hasColumn('procurement_apps', 'plan_phase')) {
                                $a->where('plan_phase', 'final');
                            }
                        }));
                }
            })
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
            'item_descriptions' => $this->item_descriptions($request->keyword),
            'ppmp_items' => $this->ppmp_items($request),
            'ppmp_projects' => $this->ppmp_projects($request),
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
        $procurement = $this->procurementForBroadcast($procurement->id);
        $this->broadcastProcurementRequestChanged($procurement, 'created');

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

        $payload['division_id'] = $request->filled('division_id')
            ? (int) $request->input('division_id')
            : $user->organization?->division_id;
        $payload['unit_id'] = $request->filled('unit_id')
            ? (int) $request->input('unit_id')
            : $user->organization?->unit_id;

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
    

    /**
     * Create or update this PR's line items from the request, matching existing rows by
     * their own 'id' when present. Rows not resubmitted are deleted. Matching by id (instead
     * of always delete-then-recreate) keeps each item's primary key stable across edits, so a
     * ProcurementQuotationItem created earlier against procurement_item_id doesn't silently
     * dangle the next time the PR is reviewed/approved/edited.
     */
    protected function saveProcurementItems($request ,$procurement_id ){

        $keepIds = [];

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

            // A manually-entered item (no ppmp_item_id, or a stale/deleted one) must still
            // carry its own unit type/cost/quantity — without this check a missing key here
            // throws a raw "Undefined array key" fatal instead of a clean error.
            if (!isset($item['item_unit_type_id'], $item['item_unit_cost'], $item['item_quantity'])) {
                throw ValidationException::withMessages([
                    "items.{$index}" => 'Each item must have a unit type, unit cost, and quantity.',
                ]);
            }

            $existingId = !empty($item['id']) ? (int) $item['id'] : null;
            $data = $existingId
                ? ProcurementItem::where('procurement_id', $procurement_id)->find($existingId)
                : null;
            $data = $data ?: new ProcurementItem();

            $data->item_no = $index + 1;
            $data->procurement_id = $procurement_id;
            $data->ppmp_item_id = $item['ppmp_item_id'] ?? null;
            $data->item_unit_type_id =  $item['item_unit_type_id'];
            // procurement_items.item_name is also NOT NULL.
            $data->item_name = $item['item_name'] ?? '';
            $data->item_unit_cost = $item['item_unit_cost'];
            $data->item_quantity = $item['item_quantity'];
            // procurement_items.item_description is NOT NULL — a PPMP item with no
            // description (or a manually-entered item that skipped it) must not pass null.
            $data->item_description = $item['item_description'] ?? '';
            // Never trust a client-supplied total: an understated total_cost would slip
            // past the PAP budget check while the real qty x unit cost is higher.
            $data->total_cost = $this->lineTotal($item);
            if (!$data->exists) {
                $data->status_id = ListStatus::getID('Pending','Procurement');
            }
            $data->save();
            $keepIds[] = $data->id;
        }

        ProcurementItem::where('procurement_id', $procurement_id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * Authoritative line total: quantity x unit cost, computed server-side in cents.
     */
    protected function lineTotal(array $item): float
    {
        $quantity = (float) ($item['item_quantity'] ?? 0);
        $unitCost = (float) ($item['item_unit_cost'] ?? 0);

        return $this->centsToAmount((int) round($quantity * $unitCost * 100));
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
        $this->ensureItemsAreStillEditable($id);

        // update Procurement
        $data = $this->updatePR($id , $request);

        // update Procurement PAP Codes
        $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

        // update Procurement Item Details
        $this->updatePRItems($id , $request);

        $data = $this->procurementForBroadcast($id);
        $this->broadcastProcurementRequestChanged($data, 'updated');

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement updated successfuly!',
            'info' => "You've successfully updated the Procurement.",
        ];
    }
    
   
    public function review($id, $request)
    {
        $this->gate->authorize(ProcurementGate::REVIEW_PR, 'code');
        $this->ensureItemsAreStillEditable($id);

        $user = Auth::user();
        Log::info('Procurement review started', [
            'procurement_id' => $id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'review_procurement'
        ]);

        try {
            $currentAppId = $this->currentAppIdForRequest($request);

            if ($currentAppId) {
                $request->merge(['procurement_app_id' => $currentAppId]);
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
            $data = $this->procurementForBroadcast($id);
            $this->broadcastProcurementRequestChanged($data, 'status-updated');

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
        $this->gate->authorize(ProcurementGate::APPROVE_PR, 'code');
        $this->ensureItemsAreStillEditable($id);
        $this->ensureApproverIsNotRequester($id);

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
        $data = $this->procurementForBroadcast($id);
        $this->broadcastProcurementRequestChanged($data, 'status-updated');

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
        $data = $this->procurementForBroadcast($id);
        $this->broadcastProcurementRequestChanged($data, 'status-updated');

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement cancelled successfully!',
            'info' => "You've successfully cancelled the purchase request.",
        ];
    }

    public function destroy($id): array
    {
        $this->gate->authorize(ProcurementGate::DELETE_PR, 'code');

        $procurement = Procurement::with('status')->findOrFail($id);

        // A PR that has entered procurement proper (bidding, award, PO) is part of the
        // audit trail — it can be cancelled, never removed.
        $deletableStatuses = ['Pending', 'Cancelled'];

        if (! in_array($procurement->status?->name, $deletableStatuses, true)) {
            throw ValidationException::withMessages([
                'code' => 'Only pending or cancelled purchase requests can be deleted. Cancel the request instead.',
            ]);
        }

        if ($procurement->quotations()->exists()) {
            throw ValidationException::withMessages([
                'code' => 'This purchase request already has Requests for Quotation and cannot be deleted.',
            ]);
        }

        // Soft delete — the record stays recoverable for audit.
        $procurement->delete();

        return [
            'data' => $id,
            'message' => 'Procurement deleted successfully!',
            'info' => "You've successfully deleted the Procurement.",
            'status' => true,
        ];
    }

    /**
     * PR items are deleted and recreated on every edit. Once RFQs exist, their
     * quotation items point at the old procurement_item ids, so re-saving items
     * orphans every bid. Freeze the item set at that point.
     */
    protected function ensureItemsAreStillEditable($procurementId): void
    {
        $procurement = Procurement::withCount('quotations')->findOrFail($procurementId);

        if ($procurement->quotations_count > 0) {
            throw ValidationException::withMessages([
                'items' => 'This purchase request already has Requests for Quotation. Its items can no longer be changed.',
            ]);
        }
    }

    /**
     * Separation of duties: the approver may not be the requester or the creator.
     */
    protected function ensureApproverIsNotRequester($procurementId): void
    {
        $procurement = Procurement::findOrFail($procurementId);
        $userId = (int) Auth::id();

        if ((int) $procurement->requested_by_id === $userId || (int) $procurement->created_by_id === $userId) {
            throw ValidationException::withMessages([
                'code' => 'You cannot approve a purchase request that you created or requested.',
            ]);
        }
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

    protected function procurementForBroadcast(int $id): Procurement
    {
        return Procurement::with([
            'division',
            'unit',
            'fund_cluster',
            'classification',
            'reference_app',
            'procurement_app',
            'codes.procurement_code',
            'items.item_unit_type',
            'items.ppmp_item.item_category',
            'items.ppmp_item.ppmp.unit',
            'created_by.profile',
            'requested_by.profile',
            'approved_by.profile',
            'status',
            'sub_status',
        ])->withCount('comments')->findOrFail($id);
    }

    protected function broadcastProcurementRequestChanged(Procurement $procurement, string $action): void
    {
        // Listeners (Index.vue/View.vue) ignore the payload and just refetch on receipt —
        // broadcasting the full ProcurementResource (items, nested ppmp/codes relations, etc.)
        // was pure waste and, for PRs with several items, routinely exceeded Pusher's payload
        // size limit ("Payload too large"). Send only what broadcastOn() needs to route it.
        broadcast(new ProcurementRequestChanged(
            ['id' => $procurement->id, 'code' => $procurement->code],
            $action
        ))->toOthers();
    }

    protected function currentAppIdForRequest($request): ?int
    {
        if (!Schema::hasTable('procurement_apps')) {
            return null;
        }

        $year = (int) now()->year;

        return ProcurementApp::query()
            ->when(ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type'), fn ($query, $appTypeId) => $query->where('app_type_id', $appTypeId))
            ->where('year', $year)
            ->orderByDesc('version')
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
        // saveProcurementItems() upserts by id and removes anything not resubmitted,
        // so no separate delete-all pass is needed here.
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

        $totalAvailableCents = $this->amountToCents($budgetCodes->sum('remaining_budget'));

        if ($remainingDeductionCents > $totalAvailableCents) {
            $required = number_format($this->centsToAmount($remainingDeductionCents), 2);
            $available = number_format($this->centsToAmount($totalAvailableCents), 2);
            throw ValidationException::withMessages([
                'budget' => "Insufficient budget for approval. Required: ₱{$required}, Available: ₱{$available}.",
            ]);
        }

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

    public function item_descriptions($item_name = null): array
    {
        $item_name = trim((string) $item_name);

        if ($item_name === '' || ! Schema::hasTable('procurement_items')) {
            return [];
        }

        return ProcurementItem::query()
            ->select('item_description')
            ->whereNotNull('item_description')
            ->where('item_description', '!=', '')
            ->whereRaw('LOWER(item_name) = LOWER(?)', [$item_name])
            ->distinct()
            ->limit(10)
            ->pluck('item_description')
            ->map(fn ($desc) => trim((string) $desc))
            ->filter()
            ->unique(fn ($desc) => mb_strtolower(strip_tags($desc)))
            ->values()
            ->all();
    }

    public function ppmp_items($request): array
    {
        $unitId = (int) $request->input('unit_id');

        if (!$unitId) {
            return [];
        }

        $ppmpUnitIds = collect([$unitId]);

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

        $consolidatedPpmpStatusIds = $this->finalPpmpStatusIds();
        $approvedAppStatusId = ListStatus::getID('Approved', 'Procurement');

        return ProcurementPpmpItem::query()
            ->with([
                'item_unit_type',
                'ppmp.reference_app',
                'ppmp.codes.procurement_code',
            ])
            ->when($usedPpmpItemIds->isNotEmpty(), function ($query) use ($usedPpmpItemIds) {
                $query->whereNotIn('id', $usedPpmpItemIds);
            })
            ->whereHas('ppmp', function ($query) use ($ppmpUnitIds, $consolidatedPpmpStatusIds, $approvedAppStatusId) {
                $query
                    ->whereIn('unit_id', $ppmpUnitIds)
                    // PPMP must be consolidated (status = Approved/Reviewed in Procurement classification)
                    ->when(!empty($consolidatedPpmpStatusIds), fn ($q) => $q->whereIn('status_id', $consolidatedPpmpStatusIds))
                    ->whereHas('reference_app', function ($referenceQuery) {
                        $referenceQuery->where('name', self::PLAN_NAME_APP);
                    });

                if (Schema::hasColumn('procurement_ppmps', 'procurement_app_id')) {
                    // PPMP must be linked to an approved Final APP (RA 9184: PRs source from Final APP only)
                    $query->whereNotNull('procurement_app_id')
                        ->when($approvedAppStatusId, fn ($q) => $q->whereHas('procurement_app', function ($appQuery) use ($approvedAppStatusId) {
                            $appQuery->where('status_id', $approvedAppStatusId);
                            if (Schema::hasColumn('procurement_apps', 'plan_phase')) {
                                $appQuery->where('plan_phase', 'final');
                            }
                        }));
                }
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

    public function ppmp_projects($request): array
    {
        $unitId = (int) $request->input('unit_id');

        if (!$unitId) {
            return [];
        }

        $consolidatedStatusIds = $this->finalPpmpStatusIds();
        $approvedAppStatusId = ListStatus::getID('Approved', 'Procurement');

        return ProcurementPpmp::query()
            ->where('unit_id', $unitId)
            ->when(!empty($consolidatedStatusIds), fn ($q) => $q->whereIn('status_id', $consolidatedStatusIds))
            ->whereHas('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_APP))
            ->when(
                Schema::hasColumn('procurement_ppmps', 'procurement_app_id'),
                fn ($q) => $q
                    ->whereNotNull('procurement_app_id')
                    ->when($approvedAppStatusId, fn ($q2) => $q2->whereHas('procurement_app', function ($a) use ($approvedAppStatusId) {
                        $a->where('status_id', $approvedAppStatusId);
                        if (Schema::hasColumn('procurement_apps', 'plan_phase')) {
                            $a->where('plan_phase', 'final');
                        }
                    }))
            )
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(function ($ppmp) use ($unitId) {
                $year = $ppmp->date ? date('Y', strtotime($ppmp->date)) : date('Y');
                $ppmpNo = 'PPMP-' . $year . '-' . str_pad((string) $ppmp->id, 4, '0', STR_PAD_LEFT);
                $label = $ppmpNo . ($ppmp->title ? ' — ' . $ppmp->title : '');

                $totalBudget = (float) $ppmp->items()->sum('total_cost');

                $ppmpItemIds = $ppmp->items()->pluck('id');
                $usedBudget = $ppmpItemIds->isNotEmpty()
                    ? (float) ProcurementItem::query()
                        ->whereNotNull('ppmp_item_id')
                        ->whereIn('ppmp_item_id', $ppmpItemIds)
                        ->whereHas('procurement', fn ($q) => $q
                            ->where('unit_id', $unitId)
                            ->where('code', 'not like', 'PPMP-%')
                            ->whereDoesntHave('status', fn ($s) => $s->where('name', 'Cancelled'))
                        )
                        ->sum('total_cost')
                    : 0.0;

                return [
                    'value'            => $ppmp->id,
                    'name'             => $label,
                    'ppmp_no'          => $ppmpNo,
                    'title'            => $ppmp->title,
                    'total_budget'     => $totalBudget,
                    'used_budget'      => $usedBudget,
                    'remaining_budget' => max(0.0, $totalBudget - $usedBudget),
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
        $approvedAppStatusId = ListStatus::getID('Approved', 'Procurement');
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
            ->whereHas('ppmp', function ($query) use ($approvedStatusIds, $fundClusterId, $approvedAppStatusId) {
                $query
                    ->whereIn('status_id', $approvedStatusIds)
                    ->where('fund_cluster_id', $fundClusterId)
                    ->whereHas('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_APP));

                if (Schema::hasColumn('procurement_ppmps', 'procurement_app_id')) {
                    $query->whereNotNull('procurement_app_id')
                        ->when($approvedAppStatusId, fn ($q) => $q->whereHas('procurement_app', function ($a) use ($approvedAppStatusId) {
                            $a->where('status_id', $approvedAppStatusId);
                            if (Schema::hasColumn('procurement_apps', 'plan_phase')) {
                                $a->where('plan_phase', 'final');
                            }
                        }));
                }
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
