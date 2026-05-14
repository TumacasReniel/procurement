<?php

namespace App\Services\FAIMS\Procurement;

use App\Http\Resources\FAIMS\Procurement\ProcurementPPMPResource;
use App\Models\ListData;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\ListUnit;
use App\Models\ProcurementPpmp;
use App\Models\ProcurementPpmpItem;
use App\Models\Request as RequestModel;
use App\Services\DropdownClass;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProcurementPPMPClass
{
    protected const PLAN_TYPE_PPMP = 'PPMP';

    protected const PLAN_TYPE_APP = 'APP';

    protected const PLAN_TYPE_SPP = 'SPP';

    protected const PLAN_NAME_APP = 'Annual Procurement Plan';

    protected const PLAN_NAME_SPP = 'Supplemental Procurement Plan';

    protected const PLAN_TITLE_PPMP = 'Project Procurement Management Plan';

    protected const STATUS_PENDING = 'Pending';

    protected const STATUS_REVIEWED = 'Reviewed';

    protected const STATUS_APPROVED = 'Approved';

    public function __construct(protected DropdownClass $dropdown) {}

    public function lists($request)
    {
        $per_page = (int) ($request->count ?? 10);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $procurements = $this->ppmpQuery($request)->get();
        $grouped = $this->groupProcurementsForList($procurements, $request);

        return ProcurementPPMPResource::collection(
            new LengthAwarePaginator(
                $grouped->forPage($page, $per_page)->values(),
                $grouped->count(),
                $per_page,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            )
        );
    }

    public function store($request)
    {
        return match ($request->option) {
            'create_ppmp' => $this->createPpmp($request),
            default => abort(404),
        };
    }

    public function updateByOption($id, $request): array
    {
        return match ($request->option) {
            'update_status', 'approve_to_app' => $this->updateStatus($id, $request),
            'add_item' => $this->addItem($id, $request),
            'update_item' => $this->updateItem($id, $request),
            'delete_item' => $this->deleteItem($id, $request),
            default => abort(404),
        };
    }

    public function indexPageProps(): array
    {
        return [
            'dropdowns' => [
                'roles' => Auth::user()->roles,
                'designation' => Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'units' => $this->dropdown->list_units(),
                'unit_types' => $this->dropdown->unit_types(),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'item_categories' => $this->dropdown->dropdowns('Item Category'),
                'mode_of_procurements' => $this->dropdown->dropdowns('Mode of Procurement'),
                'app_types' => $this->dropdown->dropdowns('APP Type'),
                'annual_app_years' => $this->registeredPlanYears(self::PLAN_NAME_APP),
            ],
        ];
    }

    public function showPageProps($id, $request)
    {
        return [
            'ppmp' => $this->show($id, $request),
            'dropdowns' => [
                'units' => $this->dropdown->list_units(),
                'unit_types' => $this->dropdown->unit_types(),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'item_categories' => $this->dropdown->dropdowns('Item Category'),
                'mode_of_procurements' => $this->dropdown->dropdowns('Mode of Procurement'),
            ],
        ];
    }

    public function availablePpmpUnits($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $used_unit_ids = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNotNull('unit_id')
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return ListUnit::query()
            ->where('is_active', 1)
            ->whereNotIn('id', $used_unit_ids)
            ->orderBy('name')
            ->get()
            ->map(fn ($unit) => [
                'value' => $unit->id,
                'name' => $unit->name,
                'short' => $unit->short,
                'division_id' => $unit->division_id,
            ])
            ->values()
            ->all();
    }

    public function show($id, $request = null): array
    {
        $request ??= (object) ['plan_type' => self::PLAN_TYPE_PPMP];
        $procurement = ProcurementPpmp::query()
            ->with($this->relations())
            ->findOrFail($id);
        $actual_plan_type = $this->actualPlanTypeForView($procurement);
        $requested_plan_type = $this->normalizePlanType(data_get($request, 'plan_type', $actual_plan_type));
        $plan_type = $requested_plan_type === self::PLAN_TYPE_PPMP ? self::PLAN_TYPE_PPMP : $actual_plan_type;

        $procurements = $this->aggregateSourceQuery($procurement, $plan_type)->get();

        switch ($plan_type) {
            case self::PLAN_TYPE_PPMP:
                $resource = $this->aggregateByUnit($procurements)->first();

                if ($resource) {
                    $this->applyOverrides($resource, [
                        'plan_name_override' => self::PLAN_TYPE_PPMP,
                        'approval_status_override' => $this->approvalStatusForGroup(
                            collect(),
                            $procurements->pluck('status.name')->filter()->unique()->values()
                        ),
                    ]);
                }

                return (new ProcurementPPMPResource($resource ?: $procurement))->resolve();

            default:
                $resource = $this->aggregateAgencyWide(
                    $procurements,
                    $this->planNameForType($plan_type)
                )->first();

                return (new ProcurementPPMPResource($resource ?: $procurement))->resolve();
        }
    }

    public function createSpp($request): array
    {
        $year = now()->year;
        $unit = ListUnit::query()->findOrFail((int) $request->unit_id);
        $app_type_id = ListDropdown::getID(self::PLAN_NAME_SPP, 'APP Type');
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);
        $fund_cluster_id = $this->regularFundClusterId();

        $this->validateSppSetup($app_type_id, $approved_status_id, $pending_status_id);
        $this->ensureApprovedAppExists($year, $approved_status_id);

        $procurement = ProcurementPpmp::query()->create($this->sppPayload(
            $year,
            $unit,
            $app_type_id,
            $approved_status_id,
            $fund_cluster_id
        ));
        $this->attachRequestIfSupported($procurement);
        ProcurementPpmpItem::query()->create($this->itemPayloadFromRequest(
            $procurement,
            $request,
            $pending_status_id,
            1
        ));

        return [
            'data' => $this->show($procurement->id, (object) ['plan_type' => self::PLAN_TYPE_SPP]),
            'message' => 'Supplemental plan item added successfully!',
            'info' => "{$request->item_name} was added to {$unit->name}'s SPP for {$year}.",
            'status' => true,
        ];
    }

    public function createPpmp($request): array
    {
        $year = (int) $request->year;
        $unit = ListUnit::query()->findOrFail((int) $request->unit_id);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);
        $fund_cluster_id = $this->regularFundClusterId();

        $this->validatePpmpSetup($pending_status_id, $fund_cluster_id);
        $this->ensureUnitHasNoPpmpForYear($unit, $year);

        $procurement = ProcurementPpmp::query()->create($this->ppmpPayload(
            $year,
            $unit,
            $pending_status_id,
            $fund_cluster_id
        ));

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP created successfully!',
            'info' => "{$unit->name} now has an indicative PPMP for {$year}.",
            'status' => true,
        ];
    }

    protected function generateUnitPpmpCode(int $year, int $unit_id): string
    {
        $base_code = 'PPMP-'.$year.'-'.str_pad((string) $unit_id, 3, '0', STR_PAD_LEFT);

        if (! ProcurementPpmp::query()->where('code', $base_code)->exists()) {
            return $base_code;
        }

        $existing_codes = ProcurementPpmp::query()
            ->where('code', 'like', $base_code.'-%')
            ->pluck('code')
            ->all();
        $next_number = collect($existing_codes)
            ->map(function ($code) use ($base_code) {
                $suffix = str_replace($base_code.'-', '', (string) $code);

                return ctype_digit($suffix) ? (int) $suffix : 1;
            })
            ->push(1)
            ->max() + 1;

        return $base_code.'-'.str_pad((string) $next_number, 2, '0', STR_PAD_LEFT);
    }

    public function updateStatus($id, $request): array
    {
        switch (data_get($request, 'plan_type', self::PLAN_TYPE_PPMP)) {
            case self::PLAN_TYPE_APP:
            case 'annual':
                return $this->advancePpmpStatus($id, $request);

            case self::PLAN_TYPE_SPP:
            case 'supplemental':
                return $this->advancePpmpStatus($id, $request);

            case self::PLAN_TYPE_PPMP:
            case 'ppmp':
            default:
                switch ($request->option) {
                    case 'approve_to_app':
                        return $this->consolidatePpmpToApp($id, $request);

                    default:
                        return $this->advancePpmpStatus($id, $request);
                }
        }
    }

    protected function consolidatePpmpToApp($id, $request): array
    {
        $procurement = ProcurementPpmp::query()
            ->with(['status', 'reference_app'])
            ->findOrFail($id);

        $app_type_id = ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type');
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);

        $this->validateAppSetup($app_type_id, $approved_status_id);
        $this->ensurePpmpCanBeConsolidated($procurement, $approved_status_id);

        $procurement->update([
            'reference_app_id' => $app_type_id,
            'status_id' => $approved_status_id,
            'approved_by_id' => Auth::id(),
            'updated_at' => now(),
        ]);

        return [
            'data' => [
                'id' => $procurement->id,
                'year' => (int) date('Y', strtotime((string) $procurement->date)),
                'plan_type' => 'annual',
                'reference_app_id' => $app_type_id,
            ],
            'message' => 'PPMP consolidated successfully!',
            'info' => 'The selected PPMP was consolidated and added to the APP.',
            'status' => true,
        ];
    }

    protected function advancePpmpStatus($id, $request = null)
    {
        $procurement = ProcurementPpmp::query()
            ->with($this->relations())
            ->findOrFail($id);

        $this->ensurePpmpNotConsolidated($procurement);

        $status_ids = $this->submissionStatusIds();

        $current_status_id = (int) $procurement->status_id;
        $next_step = $this->nextSubmissionStep(
            $current_status_id,
            $status_ids['pending'],
            $status_ids['reviewed'],
            $status_ids['approved']
        );

        $year = $this->yearForProcurement($procurement);
        $plan_type = data_get($request, 'plan_type', self::PLAN_TYPE_PPMP);
        $should_update_all_units = false;

        switch ($plan_type) {
            case self::PLAN_TYPE_APP:
            case 'annual':
                $should_update_all_units = true;
                break;

            case self::PLAN_TYPE_SPP:
            case 'supplemental':
            case self::PLAN_TYPE_PPMP:
            case 'ppmp':
            default:
                $should_update_all_units = false;
                break;
        }

        $updated = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNull('reference_app_id')
            ->where('status_id', $current_status_id)
            ->when(! $should_update_all_units, fn ($query) => $query->where('unit_id', $procurement->unit_id))
            ->update([
                'status_id' => $next_step['status_id'],
                'approved_by_id' => Auth::id(),
                'updated_at' => now(),
            ]);

        return [
            'data' => $this->show($id),
            'message' => $next_step['message'],
            'info' => "{$updated} PPMP ".($updated === 1 ? 'entry was' : 'entries were')." moved to {$next_step['label']}. {$next_step['info']}",
            'status' => true,
        ];
    }

    public function addItem($id, $request)
    {
        $procurement = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be added while the PPMP is still indicative.',
            ]);
        }

        $procurement->title = $request->general_description_objective;
        $procurement->save();

        $pending_status_id = $this->statusId(self::STATUS_PENDING);
        $supporting_document = $this->storeSupportingDocument($request);
        $next_item_no = $this->nextItemNumber($procurement);
        $rows = $this->itemRowsFromRequest($request);

        $created_items = $rows->map(function ($row, $index) use ($procurement, $request, $pending_status_id, $supporting_document, $next_item_no) {
            return ProcurementPpmpItem::query()->create($this->itemPayloadFromRequest(
                $procurement,
                $request,
                $pending_status_id,
                $next_item_no + $index,
                $supporting_document,
                $row
            ));
        });

        return [
            'data' => $this->show($procurement->id),
            'message' => $created_items->count() === 1 ? 'PPMP item added successfully!' : 'PPMP items added successfully!',
            'info' => $created_items->count().' '.($created_items->count() === 1 ? 'item was' : 'items were')." added to {$procurement->code}.",
            'status' => true,
        ];
    }

    public function updateItem($id, $request)
    {
        $procurement = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be edited while the PPMP is still indicative.',
            ]);
        }

        $item = ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->findOrFail($request->item_id);

        $procurement->title = $request->general_description_objective;
        $procurement->save();

        $item->fill($this->editableItemPayload($request));

        if ($request->hasFile('supporting_document_file')) {
            $supporting_document = $this->storeSupportingDocument($request);
            $item->fill([
                'supporting_document_path' => $supporting_document['path'],
                'supporting_document_original_name' => $supporting_document['original_name'],
            ]);
        }

        $item->save();

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP item updated successfully!',
            'info' => "{$item->item_name} was updated.",
            'status' => true,
        ];
    }

    public function deleteItem($id, $request): array
    {
        $procurement = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be deleted while the PPMP is still indicative.',
            ]);
        }

        $item = ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->findOrFail($request->item_id);
        $item_name = $item->item_name;

        $item->delete();

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP item deleted successfully!',
            'info' => "{$item_name} was removed from {$procurement->code}.",
            'status' => true,
        ];
    }

    protected function groupProcurementsForList(Collection $procurements, $request): Collection
    {
        switch (true) {
            case $this->isAgencyWidePlan($request->plan_type):
                return $this->aggregateAgencyWideByYear(
                    $procurements,
                    $this->planNameForType($request->plan_type)
                );

            default:
                return $this->aggregateByUnit($procurements, $request->sort);
        }
    }

    protected function validateSppSetup(?int $app_type_id, ?int $approved_status_id, ?int $pending_status_id): void
    {
        if (! $app_type_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Supplemental Procurement Plan is not configured in APP Type dropdowns.',
            ]);
        }

        if (! $approved_status_id || ! $pending_status_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Approved or Pending procurement status is not configured.',
            ]);
        }
    }

    protected function validatePpmpSetup(?int $pending_status_id, ?int $fund_cluster_id): void
    {
        if (! $pending_status_id) {
            throw ValidationException::withMessages([
                'unit_id' => 'Pending procurement status is not configured.',
            ]);
        }

        if (! $fund_cluster_id) {
            throw ValidationException::withMessages([
                'unit_id' => 'Fund Cluster dropdown is not configured.',
            ]);
        }
    }

    protected function validateAppSetup(?int $app_type_id, ?int $approved_status_id): void
    {
        if (! $app_type_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Annual Procurement Plan is not configured in APP Type dropdowns.',
            ]);
        }

        if (! $approved_status_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Approved procurement status is not configured.',
            ]);
        }
    }

    protected function ensurePpmpCanBeConsolidated(ProcurementPpmp $procurement, int $approved_status_id): void
    {
        if ($procurement->status_id === $approved_status_id && ! $procurement->reference_app_id) {
            return;
        }

        throw ValidationException::withMessages([
            'plan_type' => 'Only PPMP entries marked Submitted/For Consolidation can be consolidated.',
        ]);
    }

    protected function ensurePpmpNotConsolidated(ProcurementPpmp $procurement): void
    {
        if (! $procurement->reference_app_id) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'This PPMP is already included in an APP/SPP.',
        ]);
    }

    protected function submissionStatusIds(): array
    {
        $status_ids = [
            'pending' => $this->statusId(self::STATUS_PENDING),
            'reviewed' => $this->statusId(self::STATUS_REVIEWED),
            'approved' => $this->statusId(self::STATUS_APPROVED),
        ];

        if (! $status_ids['pending'] || ! $status_ids['reviewed'] || ! $status_ids['approved']) {
            throw ValidationException::withMessages([
                'ppmp' => 'Pending, Reviewed, or Approved procurement status is not configured.',
            ]);
        }

        return $status_ids;
    }

    protected function ensureApprovedAppExists(int $year, int $approved_status_id): void
    {
        $has_approved_app = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->where('status_id', $approved_status_id)
            ->whereHas('reference_app', function ($reference_query) {
                $reference_query->where('name', self::PLAN_NAME_APP);
            })
            ->exists();

        if (! $has_approved_app) {
            throw ValidationException::withMessages([
                'plan_type' => 'APP must be approved for the current year before creating an SPP update.',
            ]);
        }
    }

    protected function ensureUnitHasNoPpmpForYear(ListUnit $unit, int $year): void
    {
        $exists = ProcurementPpmp::query()
            ->where('unit_id', $unit->id)
            ->whereYear('date', $year)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'unit_id' => 'This unit already has a PPMP for the selected year.',
            ]);
        }
    }

    protected function sppPayload(int $year, ListUnit $unit, int $app_type_id, int $approved_status_id, ?int $fund_cluster_id): array
    {
        return [
            'code' => $this->generateSppCode($year, (int) $unit->id),
            'date' => $year.'-01-01',
            'purpose' => 'Supplemental Procurement Plan update for '.$unit->name,
            'title' => self::PLAN_NAME_SPP,
            'division_id' => $unit->division_id,
            'unit_id' => $unit->id,
            'fund_cluster_id' => $fund_cluster_id,
            'reference_app_id' => $app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'approved_by_id' => Auth::id(),
            'status_id' => $approved_status_id,
        ];
    }

    protected function ppmpPayload(int $year, ListUnit $unit, int $pending_status_id, int $fund_cluster_id): array
    {
        $payload = [
            'code' => $this->generateUnitPpmpCode($year, (int) $unit->id),
            'date' => $year.'-01-01',
            'purpose' => self::PLAN_TITLE_PPMP.' for '.$unit->name,
            'title' => self::PLAN_TITLE_PPMP,
            'division_id' => $unit->division_id,
            'unit_id' => $unit->id,
            'fund_cluster_id' => $fund_cluster_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $pending_status_id,
        ];

        if (Schema::hasColumn('procurement_ppmps', 'request_id')) {
            $payload['request_id'] = $this->createPpmpRequest()->id;
        }

        return $payload;
    }

    protected function generateSppCode(int $year, int $unit_id): string
    {
        $sequence = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->where('unit_id', $unit_id)
            ->count() + 1;

        return 'SPP-'.$year.'-UNIT-'.str_pad((string) $unit_id, 3, '0', STR_PAD_LEFT).'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    protected function attachRequestIfSupported(ProcurementPpmp $procurement): void
    {
        if (! Schema::hasColumn('procurement_ppmps', 'request_id')) {
            return;
        }

        $procurement->request_id = $this->createPpmpRequest()->id;
        $procurement->save();
    }

    protected function itemPayloadFromRequest(ProcurementPpmp $procurement, $request, ?int $status_id, int $item_no, ?array $supporting_document = null, $row = null): array
    {
        $supporting_document ??= $this->storeSupportingDocument($request);
        $quantity = (float) data_get($row, 'item_quantity', $request->item_quantity ?? 0);
        $unit_cost = (float) data_get($row, 'item_unit_cost', $request->item_unit_cost ?? 0);

        return [
            'item_no' => $item_no,
            'procurement_ppmp_id' => $procurement->id,
            'item_unit_type_id' => data_get($row, 'item_unit_type_id', $request->item_unit_type_id),
            'item_name' => data_get($row, 'item_name', $request->item_name),
            'item_description' => data_get($row, 'item_description', $request->item_description),
            'project_type' => $request->project_type,
            'item_category_id' => $request->item_category_id,
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'pre_procurement_conference' => $request->pre_procurement_conference,
            'end_of_procurement_activity' => $request->end_of_procurement_activity,
            'expected_delivery_date' => $request->expected_delivery_date,
            'attached_supporting_documents' => $request->attached_supporting_documents,
            'supporting_document_path' => $supporting_document['path'],
            'supporting_document_original_name' => $supporting_document['original_name'],
            'remarks' => $request->remarks,
            'item_quantity' => $quantity,
            'item_unit_cost' => $unit_cost,
            'total_cost' => $quantity * $unit_cost,
            'status_id' => $status_id,
        ];
    }

    protected function editableItemPayload($request): array
    {
        $quantity = (float) $request->item_quantity;
        $unit_cost = (float) $request->item_unit_cost;

        return [
            'item_name' => $request->item_name,
            'item_description' => $request->item_description,
            'project_type' => $request->project_type,
            'item_category_id' => $request->item_category_id,
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'pre_procurement_conference' => $request->pre_procurement_conference,
            'end_of_procurement_activity' => $request->end_of_procurement_activity,
            'expected_delivery_date' => $request->expected_delivery_date,
            'attached_supporting_documents' => $request->attached_supporting_documents,
            'remarks' => $request->remarks,
            'item_quantity' => $quantity,
            'item_unit_type_id' => $request->item_unit_type_id,
            'item_unit_cost' => $unit_cost,
            'total_cost' => $quantity * $unit_cost,
        ];
    }

    protected function itemRowsFromRequest($request): Collection
    {
        if ($request->items) {
            return collect($request->items);
        }

        return collect([[
            'item_name' => $request->item_name,
            'item_description' => $request->item_description,
            'item_quantity' => $request->item_quantity,
            'item_unit_type_id' => $request->item_unit_type_id,
            'item_unit_cost' => $request->item_unit_cost,
        ]]);
    }

    protected function nextItemNumber(ProcurementPpmp $procurement): int
    {
        return ((int) ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->max('item_no')) + 1;
    }

    protected function nextSubmissionStep(int $current_status_id, int $pending_status_id, int $reviewed_status_id, int $approved_status_id): array
    {
        switch ($current_status_id) {
            case $pending_status_id:
                return [
                    'status_id' => $reviewed_status_id,
                    'label' => 'Reviewed/For Submission',
                    'message' => 'PPMP reviewed successfully.',
                    'info' => 'The PPMP is now reviewed and ready for Procurement Officer submission.',
                ];

            case $reviewed_status_id:
                return [
                    'status_id' => $approved_status_id,
                    'label' => 'Submitted/For Consolidation',
                    'message' => 'PPMP submitted successfully.',
                    'info' => 'The PPMP is now submitted and ready for BAC consolidation.',
                ];

            default:
                throw ValidationException::withMessages([
                    'ppmp' => 'Only Pending or Reviewed PPMP entries can be advanced.',
                ]);
        }
    }

    protected function relations(): array
    {
        return [
            'division',
            'unit',
            'fund_cluster',
            'classification',
            'reference_app',
            'created_by.profile',
            'created_by.org_chart.designation',
            'created_by.organization.position',
            'requested_by.profile',
            'approved_by.profile',
            'codes.procurement_code.mode_of_procurement',
            'codes.procurement_code.app_type',
            'items.procurement',
            'items.pr_items.procurement',
            'items.item_unit_type',
            'items.item_category',
            'items.status',
            'status',
            'sub_status',
        ];
    }

    protected function storeSupportingDocument($request): array
    {
        if (! $request->hasFile('supporting_document_file')) {
            return [
                'path' => null,
                'original_name' => null,
            ];
        }

        $file = $request->file('supporting_document_file');

        return [
            'path' => $file->store('procurement/ppmp/supporting-documents', 'public'),
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    protected function statusId(string $name): ?int
    {
        $id = ListStatus::getID($name, 'Procurement');

        return $id ? (int) $id : null;
    }

    protected function regularFundClusterId(): ?int
    {
        $id = ListDropdown::getID('Regular Fund', 'Fund Cluster')
            ?: ListDropdown::query()
                ->where(function ($query) {
                    $query->where('classification', 'Fund Cluster')
                        ->orWhere('type', 'Fund Cluster');
                })
                ->value('id');

        return $id ? (int) $id : null;
    }

    protected function isLockedForItemChanges(ProcurementPpmp $procurement): bool
    {
        return $procurement->reference_app_id
            || in_array($procurement->status?->name, [self::STATUS_REVIEWED, self::STATUS_APPROVED], true);
    }

    protected function createPpmpRequest(): RequestModel
    {
        $type_id = ListData::getID('Procurement');

        if (! $type_id) {
            throw ValidationException::withMessages([
                'unit_id' => 'Procurement request type is not configured.',
            ]);
        }

        return RequestModel::query()->create([
            'code' => $this->generatePpmpRequestCode(),
            'type_id' => $type_id,
            'user_id' => Auth::id(),
            'is_completed' => 0,
            'is_sender_viewed' => 0,
            'is_receiver_viewed' => 0,
        ]);
    }

    protected function generatePpmpRequestCode(): string
    {
        $latest = RequestModel::query()
            ->lockForUpdate()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderByDesc('id')
            ->first();

        $count = $latest
            ? (int) substr($latest->code, -4) + 1
            : 1;

        return 'REQUEST-'.now()->format('mY').'-PPMP-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function registeredPlanYears(string $plan_name): array
    {
        return ProcurementPpmp::query()
            ->whereHas('reference_app', function ($reference_query) use ($plan_name) {
                $reference_query->where('name', $plan_name);
            })
            ->whereNotNull('date')
            ->selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();
    }

    protected function applyEmployeeScope($query, ?int $employee_unit_id): void
    {
        if ($employee_unit_id) {
            $query->where('unit_id', $employee_unit_id);
        }
    }

    protected function applyKeywordSearch($query, ?string $keyword): void
    {
        if (! $keyword) {
            return;
        }

        $query->where(function ($search_query) use ($keyword) {
            $search_query->where('code', 'LIKE', "%{$keyword}%")
                ->orWhere('purpose', 'LIKE', "%{$keyword}%")
                ->orWhere('title', 'LIKE', "%{$keyword}%")
                ->orWhere('date', 'LIKE', "%{$keyword}%")
                ->orWhereHas('unit', function ($unit_query) use ($keyword) {
                    $unit_query->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('short', 'LIKE', "%{$keyword}%");
                })
                ->orWhereHas('division', function ($division_query) use ($keyword) {
                    $division_query->where('name', 'LIKE', "%{$keyword}%");
                })
                ->orWhereHas('codes.procurement_code', function ($code_query) use ($keyword) {
                    $code_query->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('title', 'LIKE', "%{$keyword}%");
                });
        });
    }

    protected function applyListFilters($query, $request, ?int $employee_unit_id): void
    {
        if ($request->status) {
            $query->where('status_id', $request->status);
        }

        if (! $employee_unit_id && $request->unit && ! $this->isAgencyWidePlan($request->plan_type)) {
            $query->where('unit_id', $request->unit);
        }

        $this->applyPlanTypeFilter($query, $request->plan_type);
    }

    protected function applyPlanTypeFilter($query, ?string $plan_type): void
    {
        switch ($this->normalizePlanType($plan_type)) {
            case self::PLAN_TYPE_APP:
                $query->whereHas('reference_app', function ($reference_query) {
                    $reference_query->where('name', self::PLAN_NAME_APP);
                });
                break;

            case self::PLAN_TYPE_SPP:
                $query->whereHas('reference_app', function ($reference_query) {
                    $reference_query->where('name', self::PLAN_NAME_SPP);
                });
                break;

            case self::PLAN_TYPE_PPMP:
            default:
                $query->where(function ($ppmp_query) {
                    $ppmp_query->whereNull('reference_app_id')
                        ->orWhereHas('reference_app', function ($reference_query) {
                            $reference_query->where('name', self::PLAN_NAME_APP);
                        });
                });
                break;
        }
    }

    protected function applySort($query, ?string $sort): void
    {
        switch ($sort) {
            case 'oldest':
                $query->orderBy('date', 'ASC')->orderBy('created_at', 'ASC');
                break;

            case 'pr_asc':
                $query->orderBy('code', 'ASC');
                break;

            case 'pr_desc':
                $query->orderBy('code', 'DESC');
                break;

            default:
                $query->orderBy('date', 'DESC')->orderBy('created_at', 'DESC');
                break;
        }
    }

    protected function ppmpQuery($request)
    {
        $employee_unit_id = $this->employeeOnlyUnitId();

        $query = ProcurementPpmp::query()->with($this->relations());

        $this->applyEmployeeScope($query, $employee_unit_id);
        $this->applyKeywordSearch($query, $request->keyword);
        $this->applyListFilters($query, $request, $employee_unit_id);
        $this->applySort($query, $request->sort);

        return $query;
    }

    protected function aggregateSourceQuery(ProcurementPpmp $procurement, ?string $plan_type = null)
    {
        $employee_unit_id = $this->employeeOnlyUnitId();
        $year = $this->yearForProcurement($procurement);
        $plan_type = $this->normalizePlanType($plan_type);
        $query = ProcurementPpmp::query()
            ->with($this->relations())
            ->when($employee_unit_id, fn ($query, $unit_id) => $query->where('unit_id', $unit_id));

        switch ($plan_type) {
            case self::PLAN_TYPE_APP:
                return $query->whereYear('date', $year)
                    ->where(function ($annual_query) {
                        $annual_query->whereHas('reference_app', function ($reference_query) {
                            $reference_query->where('name', self::PLAN_NAME_APP);
                        })
                            ->orWhereHas('status', function ($status_query) {
                                $status_query->where('name', self::STATUS_APPROVED);
                            });
                    });

            case self::PLAN_TYPE_SPP:
                return $query->whereYear('date', $year)
                    ->whereHas('reference_app', function ($reference_query) {
                        $reference_query->where('name', self::PLAN_NAME_SPP);
                    });

            default:
                return $query->where('unit_id', $procurement->unit_id)
                    ->whereYear('date', $year)
                    ->where(function ($ppmp_query) {
                        $ppmp_query->whereNull('reference_app_id')
                            ->orWhereHas('reference_app', function ($reference_query) {
                                $reference_query->where('name', self::PLAN_NAME_APP);
                            });
                    });
        }
    }

    protected function aggregateByUnit(Collection $procurements, ?string $sort = null): Collection
    {
        return $procurements
            ->groupBy('unit_id')
            ->map(function (Collection $unit_procurements) {
                $representative = $unit_procurements->first();
                $items = $unit_procurements->flatMap(fn ($procurement) => $procurement->items ?? collect())->values();
                $codes = $unit_procurements
                    ->flatMap(fn ($procurement) => $procurement->codes ?? collect())
                    ->unique('procurement_code_id')
                    ->values();
                $plan_names = $unit_procurements
                    ->pluck('reference_app.name')
                    ->filter()
                    ->unique()
                    ->values();
                $pr_nos = $unit_procurements
                    ->pluck('code')
                    ->filter()
                    ->unique()
                    ->values();
                $classification_names = $unit_procurements
                    ->pluck('classification.name')
                    ->filter()
                    ->unique()
                    ->values();
                $fund_sources = $unit_procurements
                    ->pluck('fund_cluster.name')
                    ->filter()
                    ->unique()
                    ->values();
                $year = $this->yearForProcurement($representative);
                $statuses = $unit_procurements->pluck('status.name')->filter()->unique()->values();
                switch (true) {
                    case $plan_names->isNotEmpty():
                        $plan_name = $plan_names->implode(', ');
                        break;

                    default:
                        $plan_name = null;
                        break;
                }

                switch (true) {
                    case $plan_names->count() === 1 && $plan_names->first() === self::PLAN_NAME_SPP:
                        $number_prefix = self::PLAN_TYPE_SPP;
                        break;

                    default:
                        $number_prefix = self::PLAN_TYPE_PPMP;
                        break;
                }

                $representative->setRelations([
                    'items' => $items,
                    'codes' => $codes,
                ]);

                $this->applyOverrides($representative, [
                    'ppmp_no_override' => $number_prefix.'-'.$year.'-UNIT-'.str_pad((string) $representative->unit_id, 3, '0', STR_PAD_LEFT),
                    'pr_no_override' => $pr_nos->implode(', '),
                    'plan_name_override' => $plan_name,
                    'ppmp_status_override' => $this->ppmpStatusForGroup($plan_names, $statuses),
                    'aggregated_ppmp_count' => $unit_procurements->count(),
                    'classification_override' => $classification_names->implode(', '),
                    'source_of_funds_override' => $fund_sources->implode(', '),
                    'start_date_override' => $unit_procurements->pluck('date')->filter()->sort()->first(),
                    'approval_status_override' => $this->approvalStatusForGroup($plan_names, $statuses),
                ]);

                return $representative;
            })
            ->sort(fn ($first, $second) => $this->comparePpmpGroups($first, $second, $sort))
            ->values();
    }

    protected function comparePpmpGroups(ProcurementPpmp $first, ProcurementPpmp $second, ?string $sort = null): int
    {
        $first_date = strtotime((string) ($first->date ?: $first->created_at)) ?: 0;
        $second_date = strtotime((string) ($second->date ?: $second->created_at)) ?: 0;
        $first_created = strtotime((string) $first->created_at) ?: 0;
        $second_created = strtotime((string) $second->created_at) ?: 0;

        switch ($sort) {
            case 'pr_asc':
                return strcmp((string) $first->code, (string) $second->code);

            case 'pr_desc':
                return strcmp((string) $second->code, (string) $first->code);

            case 'oldest':
                return ($first_date <=> $second_date) ?: ($first_created <=> $second_created);

            default:
                return ($second_date <=> $first_date) ?: ($second_created <=> $first_created);
        }
    }

    protected function aggregateAgencyWide(Collection $procurements, string $plan_name = self::PLAN_NAME_APP): Collection
    {
        switch (true) {
            case $procurements->isEmpty():
                return collect();

            default:
                break;
        }

        $representative = $procurements->first();
        switch ($plan_name) {
            case self::PLAN_NAME_SPP:
                $plan_short = self::PLAN_TYPE_SPP;
                break;

            default:
                $plan_short = self::PLAN_TYPE_APP;
                break;
        }
        $items = $procurements->flatMap(fn ($procurement) => $procurement->items ?? collect())->values();
        $codes = $procurements
            ->flatMap(fn ($procurement) => $procurement->codes ?? collect())
            ->unique('procurement_code_id')
            ->values();
        $pr_nos = $procurements
            ->pluck('code')
            ->filter()
            ->unique()
            ->values();
        $classification_names = $procurements
            ->pluck('classification.name')
            ->filter()
            ->unique()
            ->values();
        $fund_sources = $procurements
            ->pluck('fund_cluster.name')
            ->filter()
            ->unique()
            ->values();
        $year = $this->yearForProcurement($representative);
        $statuses = $procurements->pluck('status.name')->filter()->unique()->values();
        $plan_names = $procurements->pluck('reference_app.name')->filter()->unique()->values();
        $is_registered_plan = $plan_names->contains($plan_name);

        $representative->setRelations([
            'items' => $items,
            'codes' => $codes,
        ]);

        $this->applyOverrides($representative, [
            'ppmp_no_override' => $plan_short.'-'.$year,
            'pr_no_override' => $pr_nos->implode(', '),
            'plan_name_override' => $plan_name,
            'ppmp_status_override' => $this->ppmpStatusForGroup($plan_names, $statuses),
            'unit_override' => [
                'id' => null,
                'name' => 'Agency-wide',
                'short' => $plan_short,
            ],
            'aggregated_ppmp_count' => $procurements->count(),
            'classification_override' => $classification_names->implode(', '),
            'source_of_funds_override' => $fund_sources->implode(', '),
            'start_date_override' => $procurements->pluck('date')->filter()->sort()->first(),
            'approval_status_override' => $this->approvalStatusForPlan($is_registered_plan, $plan_name, $plan_names, $statuses),
            'source_ppmps_override' => $this->sourcePpmpSummaries($procurements, $plan_name),
        ]);

        return collect([$representative]);
    }

    protected function aggregateAgencyWideByYear(Collection $procurements, string $plan_name = self::PLAN_NAME_APP): Collection
    {
        return $procurements
            ->groupBy(fn ($procurement) => $this->yearForProcurement($procurement, true))
            ->sortKeysDesc()
            ->flatMap(fn (Collection $year_procurements) => $this->aggregateAgencyWide($year_procurements, $plan_name))
            ->values();
    }

    protected function sourcePpmpSummaries(Collection $procurements, string $plan_name = self::PLAN_NAME_APP): array
    {
        switch ($plan_name) {
            case self::PLAN_NAME_SPP:
                $approval_status = 'Consolidated/Added to SPP';
                break;

            default:
                $approval_status = 'Consolidated/Added to APP';
                break;
        }

        $eligible_procurements = $procurements->filter(function ($procurement) use ($plan_name) {
            $reference_name = $procurement->reference_app?->name;
            $status_name = $procurement->status?->name;

            switch (true) {
                case $reference_name === $plan_name:
                    return true;

                default:
                    return $status_name === self::STATUS_APPROVED;
            }
        });

        return $eligible_procurements
            ->map(function (ProcurementPpmp $procurement) use ($approval_status) {
                $items = collect($procurement->items ?? []);
                $year = $this->yearForProcurement($procurement);

                return [
                    'id' => $procurement->id,
                    'ppmp_no' => 'PPMP-'.$year.'-'.str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT),
                    'unit_id' => $procurement->unit_id,
                    'unit' => $procurement->unit?->name,
                    'division' => $procurement->division,
                    'pr_no' => $procurement->code,
                    'items_count' => $items->count(),
                    'total_amount' => $items->sum(fn ($item) => (float) ($item->total_cost ?? 0)),
                    'approval_status' => $approval_status,
                ];
            })
            ->sortBy([
                ['unit', 'asc'],
                ['ppmp_no', 'asc'],
            ])
            ->values()
            ->all();
    }

    protected function applyOverrides(ProcurementPpmp $procurement, array $overrides): ProcurementPpmp
    {
        $procurement->forceFill($overrides);

        return $procurement;
    }

    protected function yearForProcurement(?ProcurementPpmp $procurement, bool $use_created_at = false): string
    {
        switch (true) {
            case ! $procurement:
                return date('Y');

            case (bool) $procurement->date:
                return date('Y', strtotime($procurement->date));

            case $use_created_at && (bool) $procurement->created_at:
                return date('Y', strtotime((string) $procurement->created_at));

            default:
                return date('Y');
        }
    }

    protected function approvalStatusForPlan(bool $is_registered_plan, string $plan_name, Collection $plan_names, Collection $statuses): string
    {
        switch (true) {
            case ! $is_registered_plan:
                return $this->approvalStatusForGroup($plan_names, $statuses);

            case $plan_name === self::PLAN_NAME_SPP:
                return 'Consolidated/Added to SPP';

            default:
                return 'Consolidated/Added to APP';
        }
    }

    protected function planNameForType(?string $plan_type): string
    {
        switch ($this->normalizePlanType($plan_type)) {
            case self::PLAN_TYPE_SPP:
                return self::PLAN_NAME_SPP;

            default:
                return self::PLAN_NAME_APP;
        }
    }

    protected function isAgencyWidePlan(?string $plan_type): bool
    {
        switch ($this->normalizePlanType($plan_type)) {
            case self::PLAN_TYPE_APP:
            case self::PLAN_TYPE_SPP:
                return true;

            default:
                return false;
        }
    }

    protected function actualPlanTypeForView(ProcurementPpmp $procurement): string
    {
        switch ($procurement->reference_app?->name) {
            case self::PLAN_NAME_APP:
                return self::PLAN_TYPE_APP;

            case self::PLAN_NAME_SPP:
                return self::PLAN_TYPE_SPP;

            default:
                return self::PLAN_TYPE_PPMP;
        }
    }

    protected function normalizePlanType(?string $plan_type): string
    {
        switch ($plan_type) {
            case self::PLAN_TYPE_APP:
            case 'annual':
                return self::PLAN_TYPE_APP;

            case self::PLAN_TYPE_SPP:
            case 'supplemental':
                return self::PLAN_TYPE_SPP;

            case self::PLAN_TYPE_PPMP:
            case 'ppmp':
            default:
                return self::PLAN_TYPE_PPMP;
        }
    }

    protected function approvalStatusForGroup(Collection $plan_names, Collection $statuses): string
    {
        switch (true) {
            case $plan_names->contains(self::PLAN_NAME_APP) && ! $plan_names->contains(self::PLAN_NAME_SPP):
                return 'Consolidated/Added to APP';

            case $plan_names->contains(self::PLAN_NAME_SPP) && ! $plan_names->contains(self::PLAN_NAME_APP):
                return 'Consolidated/Added to SPP';

            case $plan_names->isNotEmpty():
                return 'Consolidated/Added to APP and SPP';

            case $statuses->contains(self::STATUS_APPROVED):
                return 'Submitted/For Consolidation';

            case $statuses->contains(self::STATUS_REVIEWED):
                return 'Reviewed/For Submission';

            default:
                return 'Pending';
        }
    }

    protected function employeeOnlyUnitId(): ?int
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('Employee')) {
            return null;
        }

        $has_privileged_role = $user->roles()
            ->whereIn('name', [
                'Administrator',
                'Procurement Officer',
                'Procurement Staff',
                'Budget Officer',
            ])
            ->exists();

        if ($has_privileged_role) {
            return null;
        }

        $unit_id = $user->organization?->unit_id;

        return $unit_id ? (int) $unit_id : null;
    }

    protected function ppmpStatusForGroup(Collection $plan_names, Collection $statuses): string
    {
        switch (true) {
            case $plan_names->contains(self::PLAN_NAME_SPP) && ! $plan_names->contains(self::PLAN_NAME_APP):
                return 'Consolidated/Added to SPP';

            case $plan_names->isNotEmpty():
                return 'Consolidated/Added to APP';

            case $statuses->contains(self::STATUS_APPROVED):
                return 'Submitted/For Consolidation';

            case $statuses->contains(self::STATUS_REVIEWED):
                return 'Reviewed/For Submission';

            default:
                return 'Pending';
        }
    }
}
