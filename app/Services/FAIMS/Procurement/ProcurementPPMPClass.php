<?php

namespace App\Services\FAIMS\Procurement;

use App\Http\Resources\FAIMS\Procurement\ProcurementPPMPResource;
use App\Models\ListData;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\ListUnit;
use App\Models\ProcurementApp;
use App\Models\ProcurementPpmp;
use App\Models\ProcurementPpmpItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\ProcurementPlanForReviewNotification;
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

    protected const PPMP_TYPE_INDICATIVE = 'indicative';

    protected const PPMP_TYPE_FINAL = 'final';

    // APP plan phase (RA 9184): indicative = pre-budget, final = post-GAA
    protected const PLAN_PHASE_INDICATIVE = 'indicative';

    protected const PLAN_PHASE_FINAL = 'final';

    protected const STATUS_PENDING = 'Pending';

    protected const STATUS_FOR_REVIEW = 'For Review';

    protected const STATUS_REVIEWED = 'Reviewed';

    protected const STATUS_APPROVED = 'Approved';

    public function __construct(protected DropdownClass $dropdown) {}

    public function lists($request)
    {
        if ($this->normalizePlanType($request->plan_type) === self::PLAN_TYPE_APP && $this->hasSeparateAppRegister()) {
            return $this->appLists($request);
        }

        $per_page = (int) ($request->count ?? 10);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $procurements = $this->ppmpQuery($request)->get();

        if ($this->normalizePlanType($request->plan_type) === self::PLAN_TYPE_SPP) {
            $procurements = $procurements
                ->filter(fn (ProcurementPpmp $procurement) => $this->isSppProcurement($procurement))
                ->values();
        }

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
            'finalize_to_final_app' => $this->finalizeToFinalApp($request),
            default => match ($this->normalizePlanType($request->plan_type)) {
                self::PLAN_TYPE_APP => $this->createApp($request),
                self::PLAN_TYPE_SPP => $this->createSpp($request),
                default => abort(404),
            },
        };
    }

    public function updateByOption($id, $request): array
    {
        return match ($request->option) {
            'update_status', 'approve_to_app' => $this->updateStatus($id, $request),
            'revert_status' => $this->revertStatus($id, $request),
            'add_item' => $this->addItem($id, $request),
            'update_item' => $this->updateItem($id, $request),
            'delete_item' => $this->deleteItem($id, $request),
            'clear_project' => $this->clearProject($id, $request),
            'update_project' => $this->updateProject($id, $request),
            'mark_as_final' => $this->markAsFinal($id),
            'create_revision' => $this->createRevision($id),
            default => abort(404),
        };
    }

    protected function markAsFinal(int $id): array
    {
        $procurement = ProcurementPpmp::with('items')->lockForUpdate()->findOrFail($id);

        if (($procurement->ppmp_type ?? self::PPMP_TYPE_INDICATIVE) !== self::PPMP_TYPE_INDICATIVE) {
            throw ValidationException::withMessages([
                'ppmp_type' => 'Only an indicative PPMP can be marked as final.',
            ]);
        }

        if (ProcurementPpmp::where('source_ppmp_id', $procurement->id)->where('ppmp_type', self::PPMP_TYPE_FINAL)->exists()) {
            throw ValidationException::withMessages([
                'ppmp_type' => 'A final version already exists. Use "Create Revision" to create a new version.',
            ]);
        }

        $hasItems        = $procurement->items->isNotEmpty();
        $hasProjectBudget = (float) ($procurement->project_total_budget ?? 0) > 0;

        if (! $hasItems && ! $hasProjectBudget) {
            throw ValidationException::withMessages([
                'ppmp_type' => 'Cannot mark as final: this PPMP has no items or projects added yet.',
            ]);
        }

        $totalBudget = $hasItems
            ? $procurement->items->sum(fn ($item) => (float) ($item->total_cost ?? ($item->item_quantity * $item->item_unit_cost)))
            : (float) $procurement->project_total_budget;

        if ($totalBudget <= 0) {
            throw ValidationException::withMessages([
                'ppmp_type' => 'Cannot mark as final: total budget must be greater than zero.',
            ]);
        }

        $year           = $this->yearForProcurement($procurement);
        $pendingStatusId = $this->statusId(self::STATUS_PENDING);

        $payload = $this->buildPpmpClonePayload($procurement, $year, $pendingStatusId, [
            'ppmp_type'         => self::PPMP_TYPE_FINAL,
            'ppmp_type_version' => 1,
            'source_ppmp_id'    => $procurement->id,
            'is_current'        => true,
        ]);

        $finalPpmp = ProcurementPpmp::query()->create($payload);

        $this->cloneItemsTo($procurement->items, $finalPpmp->id);

        $this->logPpmpActivity($finalPpmp, 'Final PPMP V1 created from indicative', [
            'source_ppmp_id' => $procurement->id,
            'year'           => $year,
            'unit_id'        => $procurement->unit_id,
        ]);

        return [
            'status'  => true,
            'data'    => ['new_ppmp_id' => $finalPpmp->id],
            'info'    => "Final PPMP V1 created from indicative #{$procurement->id}.",
            'message' => 'Final PPMP created successfully.',
        ];
    }

    protected function createRevision(int $id): array
    {
        $current = ProcurementPpmp::with('items')->lockForUpdate()->findOrFail($id);

        if (($current->ppmp_type ?? self::PPMP_TYPE_INDICATIVE) !== self::PPMP_TYPE_FINAL) {
            throw ValidationException::withMessages([
                'ppmp_type' => 'Only a final PPMP can be revised.',
            ]);
        }

        $notCurrent = Schema::hasColumn('procurement_ppmps', 'is_current')
            ? ! $current->is_current
            : ProcurementPpmp::where('source_ppmp_id', $current->source_ppmp_id ?? $current->id)
                ->where('ppmp_type', self::PPMP_TYPE_FINAL)
                ->where('id', '!=', $current->id)
                ->exists();

        if ($notCurrent) {
            throw ValidationException::withMessages([
                'ppmp_type' => 'Only the latest final version can be revised.',
            ]);
        }

        $year           = $this->yearForProcurement($current);
        $pendingStatusId = $this->statusId(self::STATUS_PENDING);
        $nextVersion    = ((int) ($current->ppmp_type_version ?? 1)) + 1;

        $current->update(['is_current' => false]);

        $payload = $this->buildPpmpClonePayload($current, $year, $pendingStatusId, [
            'ppmp_type'         => self::PPMP_TYPE_FINAL,
            'ppmp_type_version' => $nextVersion,
            'source_ppmp_id'    => $current->source_ppmp_id ?? $current->id,
            'is_current'        => true,
        ]);

        $revision = ProcurementPpmp::query()->create($payload);

        $this->cloneItemsTo($current->items, $revision->id);

        $prevVersion = $nextVersion - 1;
        $this->logPpmpActivity($revision, "Final PPMP V{$nextVersion} created (revision)", [
            'source_ppmp_id'    => $current->source_ppmp_id,
            'previous_ppmp_id'  => $current->id,
            'version'           => $nextVersion,
        ]);

        return [
            'status'  => true,
            'data'    => ['new_ppmp_id' => $revision->id],
            'info'    => "Final V{$nextVersion} created from V{$prevVersion}.",
            'message' => "Revision V{$nextVersion} created successfully.",
        ];
    }

    private function buildPpmpClonePayload(ProcurementPpmp $source, int $year, int $pendingStatusId, array $overrides): array
    {
        $payload = [
            'code'                            => $this->generateUnitPpmpCode($year, (int) $source->unit_id),
            'date'                            => $source->date,
            'purpose'                         => $source->purpose,
            'title'                           => $source->title,
            'division_id'                     => $source->division_id,
            'unit_id'                         => $source->unit_id,
            'fund_cluster_id'                 => $source->fund_cluster_id,
            'classification_id'               => $source->classification_id,
            'project_type'                    => $source->project_type,
            'recommended_mode_of_procurement' => $source->recommended_mode_of_procurement,
            'pre_procurement_conference'      => $source->pre_procurement_conference,
            'start_of_procurement_activity'   => $source->start_of_procurement_activity,
            'end_of_procurement_activity'     => $source->end_of_procurement_activity,
            'expected_delivery_date'          => $source->expected_delivery_date,
            'attached_supporting_documents'   => $source->attached_supporting_documents,
            'remarks'                         => $source->remarks,
            'project_total_budget'            => $source->project_total_budget,
            'attachment_path'                 => $source->attachment_path,
            'attachment_original_name'        => $source->attachment_original_name,
            'is_supplemental'                 => $source->is_supplemental ?? false,
            'requested_by_id'                 => $source->requested_by_id,
            'created_by_id'                   => Auth::id(),
            'status_id'                       => $pendingStatusId,
        ];

        if (Schema::hasColumn('procurement_ppmps', 'quarter')) {
            $payload['quarter'] = $source->quarter;
        }

        if (Schema::hasColumn('procurement_ppmps', 'request_id')) {
            $payload['request_id'] = $this->createPpmpRequest()->id;
        }

        return array_merge($payload, $overrides);
    }

    private function cloneItemsTo(\Illuminate\Support\Collection $items, int $targetPpmpId): void
    {
        foreach ($items as $item) {
            $data = $item->only([
                'item_no', 'item_unit_type_id', 'item_name', 'item_description',
                'project_type', 'item_category_id', 'recommended_mode_of_procurement',
                'pre_procurement_conference', 'start_of_procurement_activity',
                'end_of_procurement_activity', 'expected_delivery_date',
                'attached_supporting_documents', 'supporting_document_path',
                'supporting_document_original_name', 'remarks',
                'item_quantity', 'item_unit_cost', 'total_cost',
                'requested_quantity', 'funded_quantity', 'is_partial_funding',
                'price_basis', 'price_basis_amount',
                'quantity_adjustment_reason', 'price_variance_reason',
                'q1_indicative_amount', 'q2_indicative_amount',
                'q3_indicative_amount', 'q4_indicative_amount',
            ]);
            $data['procurement_ppmp_id'] = $targetPpmpId;
            ProcurementPpmpItem::query()->create($data);
        }
    }

    protected function finalizeToFinalApp($request): array
    {
        $app_id = (int) $request->app_id;

        $indicative_app = ProcurementApp::query()
            ->where('plan_phase', self::PLAN_PHASE_INDICATIVE)
            ->findOrFail($app_id);

        $year = (int) $indicative_app->year;

        $this->ensureUserCanCreateApp();
        $this->ensureAppDoesNotExist($year, self::PLAN_PHASE_FINAL);

        $app_type_id = ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type');
        $pending_status_id = $this->statusId(self::STATUS_PENDING);

        if (! $app_type_id) {
            throw ValidationException::withMessages([
                'app_id' => 'APP Type list data is missing. Please contact the administrator.',
            ]);
        }

        if (! $pending_status_id) {
            throw ValidationException::withMessages([
                'app_id' => 'Status configuration is missing. Please contact the administrator.',
            ]);
        }

        $source_ppmps = $indicative_app->source_ppmps()->lockForUpdate()->get();

        if ($source_ppmps->isEmpty()) {
            throw ValidationException::withMessages([
                'app_id' => 'This Indicative APP has no source PPMPs to finalize. Please consolidate unit PPMPs first.',
            ]);
        }

        [$next_version, $next_code] = $this->nextAppVersionAndCode($year);

        $final_app = ProcurementApp::query()->create([
            'code' => $next_code,
            'year' => $year,
            'version' => $next_version,
            'title' => self::PLAN_NAME_APP,
            'app_type_id' => $app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $pending_status_id,
            'plan_phase' => self::PLAN_PHASE_FINAL,
        ]);

        $has_consolidated_by = Schema::hasColumn('procurement_ppmps', 'consolidated_by_id');
        $has_consolidated_at = Schema::hasColumn('procurement_ppmps', 'consolidated_at');
        $has_ppmp_type = Schema::hasColumn('procurement_ppmps', 'ppmp_type');

        $count = 0;
        foreach ($source_ppmps as $procurement) {
            $updates = ['procurement_app_id' => $final_app->id];

            if ($has_ppmp_type) {
                $next_ppmp_version = $this->nextPpmpTypeVersion($year, (int) $procurement->unit_id, self::PPMP_TYPE_FINAL);
                $updates['ppmp_type'] = self::PPMP_TYPE_FINAL;
                $updates['ppmp_type_version'] = $next_ppmp_version;
            }

            if ($has_consolidated_by) {
                $updates['consolidated_by_id'] = Auth::id();
            }

            if ($has_consolidated_at) {
                $updates['consolidated_at'] = now();
            }

            $procurement->update($updates);
            $this->logPpmpActivity($procurement, 'PPMP finalized and moved to Final APP', [
                'from_indicative_app_id' => $indicative_app->id,
                'to_final_app_id' => $final_app->id,
            ]);

            $count++;
        }

        $this->logAppActivity($final_app, 'Final APP created from Indicative APP', [
            'plan_type' => self::PLAN_TYPE_APP,
            'plan_phase' => self::PLAN_PHASE_FINAL,
            'year' => $year,
            'indicative_app_id' => $indicative_app->id,
            'indicative_app_code' => $indicative_app->code,
            'ppmps_finalized' => $count,
        ]);

        return [
            'data' => $this->appResource($final_app->fresh($this->appRelations())),
            'message' => 'Final APP created successfully!',
            'info' => "{$count} PPMP ".($count === 1 ? 'entry was' : 'entries were')." finalized and consolidated into {$final_app->code} (Final APP).",
            'status' => true,
        ];
    }

    public function storeItemCategory(string $name): array
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw ValidationException::withMessages([
                'name' => 'Please enter the item category name.',
            ]);
        }

        $category = ListDropdown::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalizedName)])
            ->whereRaw('LOWER(TRIM(classification)) = ?', ['item category'])
            ->first();

        if (! $category) {
            $category = ListDropdown::create([
                'name' => $normalizedName,
                'classification' => 'Item Category',
                'type' => 'Item Category',
                'is_active' => 1,
            ]);
        }

        return [
            'data' => [
                'value' => $category->id,
                'name' => $category->name,
                'others' => $category->others,
            ],
            'message' => 'Item category saved.',
            'info' => 'Item category saved.',
            'status' => true,
        ];
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
                'supporting_document_types' => $this->supportingDocumentTypeDropdowns(),
                'app_types' => $this->dropdown->dropdowns('APP Type'),
                'annual_app_years' => $this->registeredPlanYears(self::PLAN_NAME_APP),
                'indicative_app_years' => $this->registeredAppYearsByPhase(self::PLAN_PHASE_INDICATIVE),
                'final_app_years' => $this->registeredAppYearsByPhase(self::PLAN_PHASE_FINAL),
            ],
        ];
    }

    public function showPageProps($id, $request)
    {
        return [
            'ppmp' => $this->show($id, $request),
            'versions' => $this->ppmpVersionsForView((int) $id),
            'dropdowns' => [
                'units' => $this->dropdown->list_units(),
                'unit_types' => $this->dropdown->unit_types(),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'item_categories' => $this->dropdown->dropdowns('Item Category'),
                'mode_of_procurements' => $this->dropdown->dropdowns('Mode of Procurement'),
                'supporting_document_types' => $this->supportingDocumentTypeDropdowns(),
            ],
        ];
    }

    protected function ppmpVersionsForView(int $id): array
    {
        if (! Schema::hasColumn('procurement_ppmps', 'ppmp_type')) {
            return [];
        }

        $ppmp = ProcurementPpmp::query()
            ->select(['id', 'unit_id', 'date', 'source_ppmp_id'])
            ->find($id);

        if (! $ppmp) {
            return [];
        }

        $year = $this->yearForProcurement($ppmp);

        // Collect the "family" root: if this PPMP has a source, use that source's ID as root
        $rootId = $ppmp->source_ppmp_id ?? $ppmp->id;

        // Load all PPMPs that belong to this family:
        // the root itself + anything that points to the root
        $hasIsCurrentCol = Schema::hasColumn('procurement_ppmps', 'is_current');

        $versions = ProcurementPpmp::query()
            ->select(array_filter([
                'id', 'code', 'ppmp_type', 'ppmp_type_version', 'source_ppmp_id',
                'status_id', 'date', 'created_at',
                $hasIsCurrentCol ? 'is_current' : null,
            ]))
            ->withCount('items')
            ->with('status:id,name')
            ->where('unit_id', $ppmp->unit_id)
            ->whereYear('date', $year)
            ->where(function ($q) {
                $q->whereNull('title')->orWhere('title', '!=', self::PLAN_NAME_SPP);
            })
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', 'NOT LIKE', 'SPP-%');
            })
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)
                    ->orWhere('source_ppmp_id', $rootId);
            })
            ->orderBy('ppmp_type')          // indicative first
            ->orderBy('ppmp_type_version')  // then by version asc
            ->get()
            ->map(function ($v) use ($id, $hasIsCurrentCol) {
                $type    = $v->ppmp_type ?? 'indicative';
                $version = (int) ($v->ppmp_type_version ?? 1);
                $isCurrent = $hasIsCurrentCol ? (bool) $v->is_current : ($v->id === $id);

                return [
                    'id'                => $v->id,
                    'code'              => $v->code,
                    'ppmp_type'         => $type,
                    'ppmp_type_version' => $version,
                    'ppmp_type_label'   => $type === self::PPMP_TYPE_FINAL ? 'Final' : 'Indicative',
                    'version_label'     => ($type === self::PPMP_TYPE_FINAL ? 'Final' : 'Indicative') . " V{$version}",
                    'status'            => $v->status?->name ?? 'Pending',
                    'items_count'       => (int) $v->items_count,
                    'created_at'        => $v->created_at?->format('M j, Y'),
                    'is_current'        => $isCurrent,
                    'source_ppmp_id'    => $v->source_ppmp_id,
                    'is_active_view'    => $v->id === $id,
                ];
            })
            ->values()
            ->all();

        return $versions;
    }

    public function availablePpmpUnits($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $employee_unit_id = $this->employeeOnlyUnitId();
        $hasIsSupplemental = Schema::hasColumn('procurement_ppmps', 'is_supplemental');
        $hasQuarterCol     = Schema::hasColumn('procurement_ppmps', 'quarter');

        // Get taken quarters per unit for this year
        $takenQuartersByUnit = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->when($hasIsSupplemental,
                fn ($q) => $q->where(fn ($q2) => $q2->whereNull('is_supplemental')->orWhere('is_supplemental', false)),
                fn ($q) => $q->where(function ($q2) {
                    $q2->whereNull('title')->orWhere('title', '!=', self::PLAN_NAME_SPP);
                })->where(function ($q2) {
                    $q2->whereNull('code')->orWhere('code', 'NOT LIKE', 'SPP-%');
                })
            )
            ->get(['unit_id', $hasQuarterCol ? 'quarter' : 'id'])
            ->groupBy('unit_id')
            ->map(fn ($rows) => $hasQuarterCol
                ? $rows->pluck('quarter')->filter()->unique()->sort()->values()->all()
                : [] // legacy: no quarter column → quarter tracking not available, don't block units
            );

        // Only show units that still have at least one free quarter
        $fullyTakenUnitIds = $takenQuartersByUnit
            ->filter(fn ($quarters) => count($quarters) >= 4)
            ->keys();

        $units = ListUnit::query()
            ->where('is_active', 1)
            ->whereNotIn('id', $fullyTakenUnitIds)
            ->when($employee_unit_id, fn ($query, $unit_id) => $query->where('id', $unit_id))
            ->orderBy('name')
            ->get();

        $unitIds       = $units->pluck('id')->all();
        $usersByUnit   = $this->batchUnitUsers($unitIds);

        return $units->map(fn ($unit) => [
            'value'          => $unit->id,
            'name'           => $unit->name,
            'short'          => $unit->short,
            'division_id'    => $unit->division_id,
            'users'          => $usersByUnit[$unit->id] ?? [],
            'taken_quarters' => $takenQuartersByUnit[$unit->id] ?? [],
        ])->values()->all();
    }

    public function availableSppUnits($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $employee_unit_id = $this->employeeOnlyUnitId();
        // A unit can create multiple SPPs. Eligibility depends only on having a PPMP already consolidated into the APP.
        $unit_ids = $this->consolidatedPpmpUnitIdsForYear($year);

        $units = ListUnit::query()
            ->where('is_active', 1)
            ->whereIn('id', $unit_ids)
            ->when($employee_unit_id, fn ($query, $unit_id) => $query->where('id', $unit_id))
            ->orderBy('name')
            ->get();

        $unitIds = $units->pluck('id')->all();
        $usersByUnit = $this->batchUnitUsers($unitIds);

        return $units->map(fn ($unit) => [
            'value'       => $unit->id,
            'name'        => $unit->name,
            'short'       => $unit->short,
            'division_id' => $unit->division_id,
            'users'       => $usersByUnit[$unit->id] ?? [],
        ])->values()->all();
    }

    protected function batchUnitUsers(array $unitIds): array
    {
        if (empty($unitIds)) {
            return [];
        }

        $byUnit = array_fill_keys($unitIds, []);

        User::query()
            ->with(['profile', 'org_chart.designation', 'organization'])
            ->whereHas('organization', fn ($q) => $q->whereIn('unit_id', $unitIds))
            ->where('is_active', 1)
            ->orderBy('id')
            ->get()
            ->each(function ($user) use (&$byUnit) {
                $unitId = $user->organization?->unit_id;

                if (! $unitId || ! array_key_exists($unitId, $byUnit)) {
                    return;
                }

                $designation = $user->org_chart?->designation?->name ?? null;
                $byUnit[$unitId][] = [
                    'value'       => $user->id,
                    'name'        => $user->profile?->full_name ?? $user->name,
                    'designation' => $designation,
                    'is_head'     => $this->isUnitHeadDesignation($designation),
                ];
            });

        foreach ($byUnit as &$users) {
            usort($users, fn ($a, $b) => (int) $b['is_head'] - (int) $a['is_head']);
        }
        unset($users);

        return $byUnit;
    }

    protected function unitUsers(int $unit_id): array
    {
        $byUnit = $this->batchUnitUsers([$unit_id]);

        return $byUnit[$unit_id] ?? [];
    }

    protected function isUnitHeadDesignation(?string $designation): bool
    {
        if (! $designation) {
            return false;
        }

        $lower = strtolower($designation);

        return str_contains($lower, 'chief')
            || str_contains($lower, 'head')
            || str_contains($lower, 'director')
            || str_contains($lower, 'supervisor')
            || str_contains($lower, 'manager')
            || str_contains($lower, 'officer-in-charge')
            || str_contains($lower, 'officer in charge')
            || str_contains($lower, 'oic');
    }

    public function show($id, $request = null): array
    {
        $request ??= (object) ['plan_type' => self::PLAN_TYPE_PPMP];

        if ($this->normalizePlanType(data_get($request, 'plan_type')) === self::PLAN_TYPE_APP && $this->hasSeparateAppRegister()) {
            $app = ProcurementApp::query()
                ->with($this->appRelations())
                ->findOrFail($id);

            return $this->appResource($app);
        }

        $procurement = ProcurementPpmp::query()
            ->with($this->relations())
            ->findOrFail($id);
        $actual_plan_type = $this->actualPlanTypeForView($procurement);
        $requested_plan_type = $this->normalizePlanType(data_get($request, 'plan_type', $actual_plan_type));
        $plan_type = $requested_plan_type === self::PLAN_TYPE_PPMP ? self::PLAN_TYPE_PPMP : $actual_plan_type;

        $procurements = $plan_type === self::PLAN_TYPE_SPP
            ? collect([$procurement])
            : $this->aggregateSourceQuery($procurement, $plan_type)->get();

        switch ($plan_type) {
            case self::PLAN_TYPE_PPMP:
            case self::PLAN_TYPE_SPP:
                $resource = $this->aggregateByUnit($procurements, null, $plan_type)->first();
                $resource ??= $procurement;

                $plan_names = $procurements->isEmpty()
                    ? collect([$procurement->reference_app?->name])->filter()->unique()->values()
                    : $procurements->pluck('reference_app.name')->filter()->unique()->values();
                $statuses = $procurements->isEmpty()
                    ? collect([$procurement->status?->name])->filter()->unique()->values()
                    : $procurements->pluck('status.name')->filter()->unique()->values();

                $this->applyOverrides($resource, [
                    'plan_name_override' => $plan_type === self::PLAN_TYPE_SPP
                        ? $this->planNameForType($plan_type)
                        : self::PLAN_TYPE_PPMP,
                    'ppmp_status_override' => $plan_type === self::PLAN_TYPE_SPP
                        ? $this->sppStatusForPlan($plan_names, $statuses)
                        : $this->ppmpStatusForGroup($plan_names, $statuses),
                    'approval_status_override' => $plan_type === self::PLAN_TYPE_SPP
                        ? $this->sppStatusForPlan($plan_names, $statuses)
                        : $this->approvalStatusForGroup($plan_names, $statuses),
                ]);

                return (new ProcurementPPMPResource($resource))->resolve();

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
        $year = (int) $request->year;
        $unit = ListUnit::query()->findOrFail((int) $request->unit_id);
        $app_type_id = ListDropdown::getID(self::PLAN_NAME_SPP, 'APP Type');
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);
        $fund_cluster_id = $this->regularFundClusterId();

        $this->validateSppSetup($app_type_id, $approved_status_id, $pending_status_id);
        $this->ensureUserCanCreatePlanForUnit($unit);
        $this->ensureApprovedFinalAppExists($year, $approved_status_id);
        $this->ensureUnitHasConsolidatedPpmpForYear($unit, $year);

        $sppPayload = $this->sppPayload($year, $unit, $app_type_id, $pending_status_id, $fund_cluster_id);

        if ($request->hasFile('attachment_file') && Schema::hasColumn('procurement_ppmps', 'attachment_path')) {
            $file = $request->file('attachment_file');
            $sppPayload['attachment_path'] = $file->store('procurement/ppmp/attachments', 'public');
            $sppPayload['attachment_original_name'] = $file->getClientOriginalName();
        }

        if ($request->requested_by_id) {
            $sppPayload['requested_by_id'] = (int) $request->requested_by_id;
        }

        $procurement = ProcurementPpmp::query()->create($sppPayload);
        $this->attachRequestIfSupported($procurement);
        $this->logPpmpActivity($procurement, 'SPP created', [
            'plan_type' => self::PLAN_TYPE_SPP,
            'year' => $year,
            'unit_id' => $unit->id,
        ]);

        return [
            'data' => $this->show($procurement->id, (object) ['plan_type' => self::PLAN_TYPE_SPP]),
            'message' => 'SPP created successfully!',
            'info' => "{$unit->name} now has a supplemental procurement plan for {$year}.",
            'status' => true,
        ];
    }

    public function createApp($request): array
    {
        $year = (int) $request->year;
        $plan_phase = in_array($request->plan_phase, [self::PLAN_PHASE_INDICATIVE, self::PLAN_PHASE_FINAL], true)
            ? $request->plan_phase
            : self::PLAN_PHASE_INDICATIVE;
        $app_type_id = ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type');
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);

        $this->validateAppSetup($app_type_id, $approved_status_id);
        $this->validateAppPendingSetup($pending_status_id);
        $this->ensureUserCanCreateApp();
        $this->ensureAppDoesNotExist($year, $plan_phase);

        if ($this->hasSeparateAppRegister()) {
            return $this->createSeparateApp($year, $app_type_id, $approved_status_id, $pending_status_id, $plan_phase);
        }

        $source_query = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNull('reference_app_id')
            ->where('status_id', $approved_status_id);

        $representative = (clone $source_query)
            ->with($this->relations())
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        if (! $representative) {
            throw ValidationException::withMessages([
                'year' => 'No PPMP entries are Submitted/For Consolidation for the selected year.',
            ]);
        }

        $updated = $source_query->update([
            'reference_app_id' => $app_type_id,
            'status_id' => $pending_status_id,
            'approved_by_id' => Auth::id(),
            'updated_at' => now(),
        ]);
        $this->logPpmpActivity($representative, 'APP created from approved PPMPs', [
            'plan_type' => self::PLAN_TYPE_APP,
            'year' => $year,
            'affected_ppmps' => $updated,
            'reference_app_id' => $app_type_id,
        ]);

        return [
            'data' => $this->show($representative->id, (object) ['plan_type' => self::PLAN_TYPE_APP]),
            'message' => 'APP created successfully!',
            'info' => "{$updated} PPMP ".($updated === 1 ? 'entry was' : 'entries were')." consolidated into the {$year} APP.",
            'status' => true,
        ];
    }

    public function createPpmp($request): array
    {
        $year = (int) $request->year;
        $unit = ListUnit::query()->findOrFail((int) $request->unit_id);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);
        $fund_cluster_id = $this->regularFundClusterId();

        $quarter = (int) ($request->quarter ?? (int) ceil(now()->month / 3));

        $this->ensureUserCanCreatePlanForUnit($unit);
        $this->validatePpmpSetup($pending_status_id, $fund_cluster_id);

        $payload = $this->ppmpPayload($year, $unit, $pending_status_id, $fund_cluster_id, self::PPMP_TYPE_INDICATIVE, false, $quarter);

        if (Schema::hasColumn('procurement_ppmps', 'quarter')) {
            $payload['quarter'] = $quarter;
        }

        if ($request->hasFile('attachment_file') && Schema::hasColumn('procurement_ppmps', 'attachment_path')) {
            $file = $request->file('attachment_file');
            $payload['attachment_path'] = $file->store('procurement/ppmp/attachments', 'public');
            $payload['attachment_original_name'] = $file->getClientOriginalName();
        }

        if ($request->requested_by_id) {
            $payload['requested_by_id'] = (int) $request->requested_by_id;
        }

        $procurement = ProcurementPpmp::query()->create($payload);
        $this->logPpmpActivity($procurement, 'PPMP created', [
            'plan_type' => self::PLAN_TYPE_PPMP,
            'year' => $year,
            'unit_id' => $unit->id,
        ]);

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP created successfully!',
            'info' => "{$unit->name} now has an indicative PPMP for {$year}.",
            'status' => true,
        ];
    }

    protected function createSeparateApp(int $year, int $app_type_id, int $approved_status_id, int $pending_status_id, string $plan_phase = self::PLAN_PHASE_INDICATIVE): array
    {
        $has_phase_col = Schema::hasColumn('procurement_apps', 'plan_phase');

        // For Final APP: pull in PPMPs already consolidated into an Indicative APP (they move up to Final),
        //   as well as any PPMPs explicitly marked final that have no APP yet.
        // For Indicative APP: only include PPMPs not yet in any APP.
        $source_query = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->where('status_id', $approved_status_id)
            ->when($plan_phase === self::PLAN_PHASE_FINAL && $has_phase_col, function ($q) {
                $q->where(function ($inner) {
                    // Already consolidated into an Indicative APP — move them up to the Final APP
                    $inner->whereHas('procurement_app', fn ($a) => $a->where('plan_phase', self::PLAN_PHASE_INDICATIVE))
                          // OR explicitly marked final but not yet in any APP
                          ->orWhere(function ($or) {
                              $or->where('ppmp_type', self::PPMP_TYPE_FINAL)
                                 ->whereNull('procurement_app_id');
                          });
                });
            }, function ($q) use ($has_phase_col) {
                $q->whereNull('procurement_app_id');
                if ($has_phase_col) {
                    $q->where(function ($inner) {
                        $inner->whereNull('ppmp_type')->orWhere('ppmp_type', self::PPMP_TYPE_INDICATIVE);
                    });
                }
            });

        [$next_version, $next_code] = $this->nextAppVersionAndCode($year);

        $app_payload = [
            'code' => $next_code,
            'year' => $year,
            'version' => $next_version,
            'title' => self::PLAN_NAME_APP,
            'app_type_id' => $app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $pending_status_id,
        ];

        if ($has_phase_col) {
            $app_payload['plan_phase'] = $plan_phase;
        }

        $app = ProcurementApp::query()->create($app_payload);

        $update_data = [
            'reference_app_id' => $app_type_id,
            'procurement_app_id' => $app->id,
            'updated_at' => now(),
        ];

        // Mark PPMPs as final when they're moved into a Final APP
        if ($has_phase_col && $plan_phase === self::PLAN_PHASE_FINAL) {
            $update_data['ppmp_type'] = self::PPMP_TYPE_FINAL;
        }

        $updated = $source_query->update($update_data);

        $phase_label = $this->planPhaseLabel($plan_phase);
        $info = $updated
            ? "{$updated} PPMP ".($updated === 1 ? 'entry was' : 'entries were')." linked to {$app->code} ({$phase_label})."
            : "{$app->code} ({$phase_label}) was created for {$year}. PPMPs can be consolidated into it once they are ready.";
        $this->logAppActivity($app, 'APP created', [
            'plan_type' => self::PLAN_TYPE_APP,
            'plan_phase' => $plan_phase,
            'year' => $year,
            'affected_ppmps' => $updated,
        ]);

        return [
            'data' => $this->appResource($app->fresh($this->appRelations())),
            'message' => "{$phase_label} created successfully!",
            'info' => $info,
            'status' => true,
        ];
    }

    protected function createUpdatedAppVersion(ProcurementApp $previous_app, int $approved_status_id): ProcurementApp
    {
        $next_version = ((int) ProcurementApp::query()
            ->where('year', $previous_app->year)
            ->max('version')) + 1;

        // New version inherits Approved status — SPP consolidation adds to an already-approved APP,
        // so the resulting updated version is immediately effective for PR creation.
        $new_app_payload = [
            'code' => $this->generateAppVersionCode($previous_app, $next_version),
            'year' => $previous_app->year,
            'version' => $next_version,
            'title' => $previous_app->title ?: self::PLAN_NAME_APP,
            'app_type_id' => $previous_app->app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $approved_status_id,
        ];
        // SPP updates are always against the Final APP — inherit its plan_phase
        if (Schema::hasColumn('procurement_apps', 'plan_phase')) {
            $new_app_payload['plan_phase'] = $previous_app->plan_phase ?? self::PLAN_PHASE_FINAL;
        }
        $app = ProcurementApp::query()->create($new_app_payload);

        $this->logAppActivity($app, 'APP updated version created', [
            'plan_type' => self::PLAN_TYPE_APP,
            'year' => $app->year,
            'version' => $app->version,
            'previous_app_id' => $previous_app->id,
            'previous_app_code' => $previous_app->code,
        ]);

        return $app;
    }

    protected function appHasConsolidatedPpmpSources(ProcurementApp $app, int $app_type_id): bool
    {
        return $app->source_ppmps()
            ->where('reference_app_id', $app_type_id)
            ->where(function ($query) {
                $query->whereNull('title')
                    ->orWhere('title', '!=', self::PLAN_NAME_SPP);
            })
            ->where(function ($query) {
                $query->whereNull('code')
                    ->orWhere('code', 'NOT LIKE', 'SPP-%');
            })
            ->exists();
    }

    protected function generateAppCode(int $year, int $version): string
    {
        return 'APP-'.$year.'-'.str_pad((string) $version, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Find the next available (version, code) pair for a new APP row in the given year.
     * Loads existing rows into PHP to avoid SQL type-cast issues with the year/version columns.
     *
     * @return array{0: int, 1: string}  [$version, $code]
     */
    protected function nextAppVersionAndCode(int $year): array
    {
        $existing = ProcurementApp::query()->get(['year', 'version', 'code']);

        $usedVersions = $existing
            ->filter(fn ($r) => (int) $r->year === $year && $r->version !== null)
            ->pluck('version')
            ->map(fn ($v) => (int) $v);

        $usedCodes = $existing
            ->pluck('code')
            ->map(fn ($c) => (string) $c);

        $version = 1;
        while (
            $usedVersions->contains($version)
            || $usedCodes->contains($this->generateAppCode($year, $version))
        ) {
            $version++;
        }

        return [$version, $this->generateAppCode($year, $version)];
    }

    protected function generateAppVersionCode(ProcurementApp $previous_app, int $version): string
    {
        $base_code = preg_replace('/-V\d+$/', '', (string) $previous_app->code);

        return $base_code.'-V'.str_pad((string) $version, 2, '0', STR_PAD_LEFT);
    }

    protected function advanceAppStatus(int $id): array
    {
        $app = ProcurementApp::query()
            ->with($this->appRelations())
            ->findOrFail($id);

        $this->ensureUserCanAdvanceAppStatus($app);

        $status_ids = $this->submissionStatusIds();
        $next_step = $this->nextSubmissionStep(
            $app->status_id,
            $status_ids['pending'],
            $status_ids['for_review'],
            $status_ids['reviewed'],
            $status_ids['approved'],
            true
        );

        $updates = [
            'status_id' => $next_step['status_id'],
            'updated_at' => now(),
        ];

        if ($next_step['status_id'] === $status_ids['for_review']) {
            if (Schema::hasColumn('procurement_apps', 'submitted_by_id')) {
                $updates['submitted_by_id'] = Auth::id();
            }
            if (Schema::hasColumn('procurement_apps', 'submitted_at')) {
                $updates['submitted_at'] = now();
            }
        }

        if ($next_step['status_id'] === $status_ids['reviewed']) {
            if (Schema::hasColumn('procurement_apps', 'reviewed_by_id')) {
                $updates['reviewed_by_id'] = Auth::id();
            }
            if (Schema::hasColumn('procurement_apps', 'reviewed_at')) {
                $updates['reviewed_at'] = now();
            }
        }

        if ($next_step['status_id'] === $status_ids['approved']) {
            if (Schema::hasColumn('procurement_apps', 'approved_by_id')) {
                $updates['approved_by_id'] = Auth::id();
            }
            if (Schema::hasColumn('procurement_apps', 'approved_at')) {
                $updates['approved_at'] = now();
            }
        }

        $app->update($updates);
        $this->logAppActivity($app->fresh(['status']), "APP moved to {$next_step['label']}", [
            'plan_type' => self::PLAN_TYPE_APP,
            'status_id' => $next_step['status_id'],
            'status' => $next_step['label'],
        ]);
        $this->notifyNextPlanReviewers(
            $app->fresh(['status']),
            self::PLAN_TYPE_APP,
            $next_step['status_id'],
            $status_ids
        );

        return [
            'data' => $this->appResource($app->fresh($this->appRelations())),
            'message' => $next_step['message'],
            'info' => "{$app->code} moved to {$next_step['label']}. {$next_step['info']}",
            'status' => true,
        ];
    }

    protected function can_advance_app_status(ProcurementApp $app): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Administrator')) {
            return true;
        }

        return match ($app->status?->name) {
            self::STATUS_PENDING => $user->hasRole('Procurement Staff')
                || $user->hasRole('Procurement Officer'),
            self::STATUS_FOR_REVIEW => $user->hasRole('Budget Officer') ,
            self::STATUS_REVIEWED => $user->hasRole('Procurement Officer') ,
            default => false,
        };
    }

    protected function ensureUserCanAdvanceAppStatus(ProcurementApp $app): void
    {
        if ($this->can_advance_app_status($app)) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'You are not allowed to update this APP at its current status.',
        ]);
    }

    protected function ensureUserCanCreateApp(): void
    {
        $user = Auth::user();

        if ($user && ($user->hasRole('Administrator') || $user->hasRole('Procurement Officer'))) {
            return;
        }

        throw ValidationException::withMessages([
            'plan_type' => 'Only Procurement Officers can create an APP register.',
        ]);
    }

    protected function ensureUserCanCreatePlanForUnit(ListUnit $unit): void
    {
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'unit_id' => 'You are not allowed to create this procurement plan.',
            ]);
        }

        $user_unit_id = $user->organization?->unit_id;
        $same_unit = $user_unit_id && (int) $user_unit_id === (int) $unit->id;

        if (
            $same_unit
            || $user->hasRole('Administrator')
            || $user->hasRole('Procurement Staff')
            || $user->hasRole('Procurement Officer')
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'unit_id' => 'You can only create a procurement plan for your assigned unit.',
        ]);
    }

    protected function ensureUnitHasNoPpmpThisQuarter(ListUnit $unit, int $year, int $quarter): void
    {
        $hasQuarterCol = Schema::hasColumn('procurement_ppmps', 'quarter');

        if ($hasQuarterCol) {
            $exists = ProcurementPpmp::query()
                ->where('unit_id', $unit->id)
                ->whereYear('date', $year)
                ->where('quarter', $quarter)
                ->exists();
        } else {
            $now = now()->setYear($year);
            $quarterStart = $now->copy()->startOfYear()->addMonths(($quarter - 1) * 3);
            $quarterEnd = (clone $quarterStart)->addMonths(3)->subSecond();
            $exists = ProcurementPpmp::query()
                ->where('unit_id', $unit->id)
                ->whereBetween('created_at', [$quarterStart, $quarterEnd])
                ->exists();
        }

        if ($exists) {
            throw ValidationException::withMessages([
                'unit_id' => "{$unit->name} already has a PPMP for Q{$quarter} {$year}. Only one PPMP per unit per quarter is allowed.",
            ]);
        }
    }

    protected function generateUnitPpmpCode(int $year, int $unit_id): string
    {
        return $this->generatePlanSeriesCode('PPMP-'.$year);
    }

    public function updateStatus($id, $request): array
    {
        // Consolidate to APP if approve_to_app, otherwise advance PPMP/SPP/APP status
        return $request->option === 'approve_to_app'
            ? $this->consolidatePpmpToApp($id, $request)
            : $this->advancePpmpStatus($id, $request);
    }

    public function revertStatus($id, $request): array
    {
        $plan_type = $this->normalizePlanType(data_get($request, 'plan_type', self::PLAN_TYPE_PPMP));

        if ($plan_type === self::PLAN_TYPE_APP && $this->hasSeparateAppRegister()) {
            return $this->revertAppStatus((int) $id, $request);
        }

        $procurement = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        if ($procurement->reference_app?->name === self::PLAN_NAME_APP && $plan_type !== self::PLAN_TYPE_APP) {
            throw ValidationException::withMessages([
                'ppmp' => 'A consolidated plan cannot be reverted with status revert. APP composition must be amended through a new APP/SPP version.',
            ]);
        }

        $this->ensureUserCanRevertPlanStatus();
        $status_ids = $this->submissionStatusIds();
        $previous_step = $this->previousSubmissionStep((int) $procurement->status_id, $status_ids, $plan_type === self::PLAN_TYPE_APP);
        $old_status = $procurement->status?->name;
        $updates = $this->revertAuditUpdates($previous_step['status_id'], $status_ids, 'procurement_ppmps');

        if ($plan_type === self::PLAN_TYPE_APP) {
            $updated = ProcurementPpmp::query()
                ->whereYear('date', $this->yearForProcurement($procurement))
                ->where('status_id', $procurement->status_id)
                ->whereHas('reference_app', fn ($query) => $query->where('name', self::PLAN_NAME_APP))
                ->update($updates);
        } else {
            $procurement->update($updates);
            $updated = 1;
        }

        $this->logPpmpActivity($procurement->fresh(['reference_app', 'status']), $this->planShortLabel($plan_type).' status reverted', [
            'plan_type' => $plan_type,
            'from_status' => $old_status,
            'to_status' => $previous_step['label'],
            'reason' => trim((string) $request->revert_reason),
            'affected_plans' => $updated,
        ]);

        return [
            'data' => $this->show($id, $request),
            'message' => $this->planShortLabel($plan_type).' status reverted successfully.',
            'info' => "Status reverted from {$old_status} to {$previous_step['label']}.",
            'status' => true,
        ];
    }

    protected function revertAppStatus(int $id, $request): array
    {
        $app = ProcurementApp::query()->with($this->appRelations())->findOrFail($id);
        $this->ensureUserCanRevertPlanStatus();
        $status_ids = $this->submissionStatusIds();
        $previous_step = $this->previousSubmissionStep((int) $app->status_id, $status_ids, true);
        $old_status = $app->status?->name;
        $app->update($this->revertAuditUpdates($previous_step['status_id'], $status_ids, 'procurement_apps'));

        $this->logAppActivity($app->fresh(['status']), 'APP status reverted', [
            'from_status' => $old_status,
            'to_status' => $previous_step['label'],
            'reason' => trim((string) $request->revert_reason),
        ]);

        return [
            'data' => $this->appResource($app->fresh($this->appRelations())),
            'message' => 'APP status reverted successfully.',
            'info' => "Status reverted from {$old_status} to {$previous_step['label']}.",
            'status' => true,
        ];
    }

    protected function previousSubmissionStep(int $current_status_id, array $status_ids, bool $is_app): array
    {
        return match ($current_status_id) {
            $status_ids['approved'] => [
                'status_id' => $status_ids['reviewed'],
                'label' => 'Reviewed/For Submission',
            ],
            $status_ids['reviewed'] => [
                'status_id' => $status_ids['for_review'],
                'label' => 'For Review',
            ],
            $status_ids['for_review'] => [
                'status_id' => $status_ids['pending'],
                'label' => 'Pending',
            ],
            default => throw ValidationException::withMessages([
                'ppmp' => ($is_app ? 'APP' : 'PPMP/SPP').' status can only be reverted from Submitted, Reviewed, or For Review.',
            ]),
        };
    }

    protected function revertAuditUpdates(int $target_status_id, array $status_ids, string $table): array
    {
        $updates = ['status_id' => $target_status_id, 'updated_at' => now()];
        $clear = [];

        if ($target_status_id !== $status_ids['approved']) {
            $clear = array_merge($clear, ['approved_by_id', 'approved_at']);
        }
        if (! in_array($target_status_id, [$status_ids['reviewed'], $status_ids['approved']], true)) {
            $clear = array_merge($clear, ['reviewed_by_id', 'reviewed_at']);
        }
        if ($target_status_id === $status_ids['pending']) {
            $clear = array_merge($clear, ['submitted_by_id', 'submitted_at']);
        }

        foreach (array_unique($clear) as $column) {
            if (Schema::hasColumn($table, $column)) {
                $updates[$column] = null;
            }
        }

        return $updates;
    }

    protected function ensureUserCanRevertPlanStatus(): void
    {
        $user = Auth::user();
        if ($user && ($user->hasRole('Administrator') || $user->hasRole('Procurement Officer'))) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'Only Administrators and Procurement Officers can revert plan status.',
        ]);
    }

    protected function canRevertPlanStatus(?int $status_id, bool $is_consolidated = false): bool
    {
        $user = Auth::user();
        if (! $user || $is_consolidated || ! ($user->hasRole('Administrator') || $user->hasRole('Procurement Officer'))) {
            return false;
        }

        $status_ids = $this->submissionStatusIds();

        return in_array((int) $status_id, [
            $status_ids['for_review'],
            $status_ids['reviewed'],
            $status_ids['approved'],
        ], true);
    }

    protected function consolidatePpmpToApp($id, $request): array
    {
        $procurement = ProcurementPpmp::with(['status', 'reference_app'])->lockForUpdate()->findOrFail($id);

        $app_type_id = ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type');
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);

        $is_spp_plan = $procurement->title === self::PLAN_NAME_SPP
            || $procurement->reference_app?->name === self::PLAN_NAME_SPP;

        $plan_label = $is_spp_plan ? 'SPP' : 'PPMP';
        $year = (int) date('Y', strtotime((string) $procurement->date));

        // Validate APP setup, user permission, and if this PPMP/SPP can be consolidated
        $this->validateAppSetup($app_type_id, $approved_status_id);
        $this->validateAppPendingSetup($pending_status_id);
        $this->ensureUserCanConsolidateToApp();
        $this->ensurePpmpCanBeConsolidated($procurement, $approved_status_id);
        $this->ensurePpmpHasItems($procurement);
        $updates = [
            'reference_app_id' => $app_type_id,
            'status_id' => $approved_status_id,
            'updated_at' => now(),
        ];

        if (! Schema::hasColumn('procurement_ppmps', 'consolidated_by_id')) {
            $updates['approved_by_id'] = Auth::id();
        }

        if (Schema::hasColumn('procurement_ppmps', 'consolidated_by_id')) {
            $updates['consolidated_by_id'] = Auth::id();
        }

        if (Schema::hasColumn('procurement_ppmps', 'consolidated_at')) {
            $updates['consolidated_at'] = now();
        }

        $data = [
            'id' => $procurement->id,
            'year' => $year,
            'plan_type' => 'annual',
            'reference_app_id' => $app_type_id,
        ];

        $info = "The selected {$plan_label} was consolidated and added to the APP.";

        if ($this->hasSeparateAppRegister()) {
            $hasPlanPhase = Schema::hasColumn('procurement_apps', 'plan_phase')
                && Schema::hasColumn('procurement_ppmps', 'ppmp_type');

            if (! $is_spp_plan && $hasPlanPhase) {
                // Route to the matching APP phase: indicative PPMP → Indicative APP, final PPMP → Final APP.
                $ppmp_type   = $procurement->ppmp_type ?? self::PPMP_TYPE_INDICATIVE;
                $target_phase = $ppmp_type === self::PPMP_TYPE_FINAL
                    ? self::PLAN_PHASE_FINAL
                    : self::PLAN_PHASE_INDICATIVE;

                $app = ProcurementApp::query()
                    ->where('year', $year)
                    ->where('plan_phase', $target_phase)
                    ->orderByDesc('version')
                    ->orderByDesc('id')
                    ->first();

                if (! $app) {
                    $phase_label = $ppmp_type === self::PPMP_TYPE_FINAL ? 'Final APP' : 'Indicative APP';
                    throw ValidationException::withMessages([
                        'plan_type' => "No {$phase_label} exists for {$year}. Please create it before consolidating.",
                    ]);
                }
            } else {
                $app = ProcurementApp::query()
                    ->where('year', $year)
                    ->orderByDesc('version')
                    ->orderByDesc('id')
                    ->first();

                if (! $app) {
                    throw ValidationException::withMessages([
                        'plan_type' => 'Please create the APP register for this year before consolidating PPMPs to APP.',
                    ]);
                }
            }

            // Regular PPMP can only be added while APP is still pending.
            // Approved SPPs become a new APP update/version once the previous APP is already for implementation.
            if (
                $is_spp_plan
                && (int) $app->status_id === (int) $approved_status_id
                && $this->appHasConsolidatedPpmpSources($app, $app_type_id)
            ) {
                $app = $this->createUpdatedAppVersion($app, $approved_status_id);
                $info = "The selected {$plan_label} was added to {$app->code} as APP version {$app->version}.";
            } elseif (! $is_spp_plan) {
                $this->ensureAppCanAcceptPpmp($app, $pending_status_id);
                $info = "The selected {$plan_label} was added to {$app->code}.";
            } else {
                $info = "The selected {$plan_label} was added to {$app->code}.";
            }

            $pricing_overrides = collect($request->input('consolidation_pricing', []))
                ->filter(fn (array $pricing) => ! empty($pricing['group_key']) && ! empty($pricing['method']))
                ->mapWithKeys(fn (array $pricing) => [
                    $pricing['group_key'] => [
                        'method' => $pricing['method'],
                        'manual_unit_cost' => $pricing['method'] === 'manual'
                            ? round((float) ($pricing['manual_unit_cost'] ?? 0), 2)
                            : null,
                        'updated_by_id' => Auth::id(),
                        'updated_at' => now()->toISOString(),
                    ],
                ])
                ->all();

            if ($pricing_overrides !== []) {
                $app->update([
                    'pricing_overrides' => array_merge($app->pricing_overrides ?? [], $pricing_overrides),
                ]);
            }

            $updates['procurement_app_id'] = $app->id;
            $data['year'] = (int) $app->year;
            $data['procurement_app_id'] = $app->id;
        }

        $procurement->update($updates);

        // Also consolidate all sibling PPMPs in the same unit/year that haven't been consolidated yet
        if (! $is_spp_plan) {
            ProcurementPpmp::query()
                ->where('unit_id', $procurement->unit_id)
                ->whereYear('date', $year)
                ->where('id', '!=', $procurement->id)
                ->whereNull('reference_app_id')
                ->where(function ($q) {
                    $q->whereNull('title')->orWhere('title', '!=', self::PLAN_NAME_SPP);
                })
                ->where(function ($q) {
                    $q->whereNull('code')->orWhere('code', 'NOT LIKE', 'SPP-%');
                })
                ->whereDoesntHave('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_SPP))
                ->update($updates);
        }

        $snapshot = $this->consolidationSnapshot(
            $procurement->fresh(['reference_app', 'status', 'unit', 'procurement_app']),
            $data['procurement_app_id'] ?? null
        );
        $this->logPpmpActivity($procurement->fresh(['reference_app', 'status']), "{$plan_label} consolidated to APP", [
            'plan_type' => $is_spp_plan ? self::PLAN_TYPE_SPP : self::PLAN_TYPE_PPMP,
            'year' => $year,
            'reference_app_id' => $app_type_id,
            'procurement_app_id' => $data['procurement_app_id'] ?? null,
            'consolidation_review_acknowledged' => $request->boolean('consolidation_review_acknowledged'),
            'consolidation_pricing' => $request->input('consolidation_pricing', []),
            'consolidation_snapshot' => $snapshot,
        ]);

        return [
            'data' => $data,
            'message' => "{$plan_label} consolidated successfully!",
            'info' => $info,
            'status' => true,
        ];
    }

    protected function advancePpmpStatus($id, $request = null): array
    {
        $plan_type = $this->normalizePlanType(data_get($request, 'plan_type', self::PLAN_TYPE_PPMP));

        $is_app_plan = in_array($plan_type, [self::PLAN_TYPE_APP, 'annual'], true);
        $is_spp_plan = in_array($plan_type, [self::PLAN_TYPE_SPP, 'supplemental'], true);

        // If APP has separate table/register, update APP status instead
        if ($is_app_plan && $this->hasSeparateAppRegister()) {
            return $this->advanceAppStatus($id);
        }

        $procurement = ProcurementPpmp::with($this->relations())->lockForUpdate()->findOrFail($id);
        $status_ids = $this->submissionStatusIds();
        $current_status_id = (int) $procurement->status_id;

        // Validate PPMP/SPP before advancing status
        if (! $is_app_plan) {
            if (! $is_spp_plan) {
                $this->ensurePpmpNotConsolidated($procurement);
            }

            $this->ensurePpmpHasItems($procurement);
            $this->ensureUserCanAdvancePpmpSubmission($procurement, $current_status_id, $status_ids);
        }

        $next_step = $this->nextSubmissionStep(
            $current_status_id,
            $status_ids['pending'],
            $status_ids['for_review'],
            $status_ids['reviewed'],
            $status_ids['approved'],
            $is_app_plan
        );

        $updates = [
            'status_id' => $next_step['status_id'],
            'updated_at' => now(),
        ];

        if (
            $next_step['status_id'] === $status_ids['for_review']
            && Schema::hasColumn('procurement_ppmps', 'submitted_by_id')
        ) {
            $updates['submitted_by_id'] = Auth::id();
        }

        if (
            $next_step['status_id'] === $status_ids['for_review']
            && Schema::hasColumn('procurement_ppmps', 'submitted_at')
        ) {
            $updates['submitted_at'] = now();
        }

        // Add reviewer when status becomes Reviewed
        if (
            $next_step['status_id'] === $status_ids['reviewed']
            && Schema::hasColumn('procurement_ppmps', 'reviewed_by_id')
        ) {
            $updates['reviewed_by_id'] = Auth::id();
        }

        if (
            $next_step['status_id'] === $status_ids['reviewed']
            && Schema::hasColumn('procurement_ppmps', 'reviewed_at')
        ) {
            $updates['reviewed_at'] = now();
        }

        // Add approver when status becomes Approved
        if ($next_step['status_id'] === $status_ids['approved']) {
            $updates['approved_by_id'] = Auth::id();
        }

        if (
            $next_step['status_id'] === $status_ids['approved']
            && Schema::hasColumn('procurement_ppmps', 'approved_at')
        ) {
            $updates['approved_at'] = now();
        }

        if ($is_app_plan) {
            // APP view should update all APP-related PPMP records for the same year and current status
            $updated = ProcurementPpmp::query()
                ->whereYear('date', $this->yearForProcurement($procurement))
                ->where('status_id', $current_status_id)
                ->whereHas('reference_app', fn ($query) =>
                    $query->where('name', self::PLAN_NAME_APP)
                )
                ->update($updates);
        } else {
            // PPMP: advance the selected record AND all non-consolidated sibling PPMPs in the same unit/year
            // SPP: update only the selected record
            if (! $is_spp_plan) {
                $year = $this->yearForProcurement($procurement);
                $updated = ProcurementPpmp::query()
                    ->where('unit_id', $procurement->unit_id)
                    ->whereYear('date', $year)
                    ->where('status_id', $current_status_id)
                    ->whereNull('reference_app_id')
                    ->where(function ($q) {
                        $q->whereNull('title')->orWhere('title', '!=', self::PLAN_NAME_SPP);
                    })
                    ->where(function ($q) {
                        $q->whereNull('code')->orWhere('code', 'NOT LIKE', 'SPP-%');
                    })
                    ->whereDoesntHave('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_SPP))
                    ->update($updates);

                if ($updated === 0) {
                    $procurement->update($updates);
                    $updated = 1;
                }
            } else {
                $procurement->update($updates);
                $updated = 1;
            }
        }
        $this->logPpmpActivity($procurement->fresh(['reference_app', 'status']), $this->planShortLabel($plan_type)." moved to {$next_step['label']}", [
            'plan_type' => $plan_type,
            'status_id' => $next_step['status_id'],
            'status' => $next_step['label'],
            'affected_ppmps' => $updated,
        ]);
        $this->notifyNextPlanReviewers(
            $procurement->fresh(['reference_app', 'status', 'unit']),
            $plan_type,
            $next_step['status_id'],
            $status_ids
        );

        return [
            'data' => $this->show($id, $request),
            'message' => $next_step['message'],
            'info' => "{$updated} PPMP ".($updated === 1 ? 'entry was' : 'entries were')." moved to {$next_step['label']}. {$next_step['info']}",
            'status' => true,
        ];
    }

    public function addItem($id, $request)
    {
        $procurement = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->lockForUpdate()
            ->findOrFail($id);

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be added while the PPMP is still indicative.',
            ]);
        }

        $this->ensureUserCanManagePlanItems($procurement);

        $rows = $this->itemRowsFromRequest($request);

        // If adding project-only (no items) and this PPMP already has project data, create a sibling record
        if ($rows->isEmpty() && Schema::hasColumn('procurement_ppmps', 'project_type') && ! is_null($procurement->project_type)) {
            $procurement = $this->createSiblingPpmp($procurement);
        }

        $procurement->title = $request->general_description_objective;

        $projectFields = [
            'project_type', 'recommended_mode_of_procurement', 'pre_procurement_conference',
            'start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date',
            'attached_supporting_documents', 'remarks',
        ];
        foreach ($projectFields as $field) {
            if (Schema::hasColumn('procurement_ppmps', $field) && $request->filled($field)) {
                $value = $request->input($field);
                $procurement->{$field} = in_array($field, ['start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date'], true)
                    ? $this->toFullDate($value)
                    : $value;
            }
        }

        if (Schema::hasColumn('procurement_ppmps', 'project_total_budget')) {
            $procurement->project_total_budget = $rows->isEmpty()
                ? ($request->filled('project_total_budget') ? (float) $request->input('project_total_budget') : null)
                : null; // clear budget when items are added
        }

        $procurement->save();

        $pending_status_id = $this->statusId(self::STATUS_PENDING);
        $supporting_document = $this->storeSupportingDocument($request);
        $next_item_no = $this->nextItemNumber($procurement);

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
        $this->logPpmpActivity($procurement, $this->planShortLabelForProcurement($procurement).' item added', [
            'items_count' => $created_items->count(),
            'item_ids' => $created_items->pluck('id')->values()->all(),
        ]);

        $count = $created_items->count();

        return [
            'data' => $this->show($procurement->id),
            'message' => $count === 0 ? 'Procurement project saved!' : ($count === 1 ? 'PPMP item added successfully!' : 'PPMP items added successfully!'),
            'info' => $count === 0
                ? "{$procurement->code} project details were saved."
                : $count.' '.($count === 1 ? 'item was' : 'items were')." added to {$procurement->code}.",
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

        $this->ensureUserCanManagePlanItems($procurement);

        $item = ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->findOrFail($request->item_id);

        $procurement->title = $request->general_description_objective;

        $projectFields = [
            'project_type', 'recommended_mode_of_procurement', 'pre_procurement_conference',
            'start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date',
            'attached_supporting_documents', 'remarks',
        ];
        foreach ($projectFields as $field) {
            if (Schema::hasColumn('procurement_ppmps', $field) && $request->filled($field)) {
                $value = $request->input($field);
                $procurement->{$field} = in_array($field, ['start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date'], true)
                    ? $this->toFullDate($value)
                    : $value;
            }
        }

        $procurement->save();

        $rows = $this->itemRowsFromRequest($request);
        $existing_rows = $rows->filter(fn ($row) => ! empty(data_get($row, 'id')));
        $new_rows = $rows->filter(fn ($row) => empty(data_get($row, 'id')))->values();

        $row_ids = $existing_rows->pluck('id')->map(fn ($id) => (int) $id)->values();

        $items = ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->whereIn('id', $row_ids)
            ->get()
            ->keyBy('id');

        if ($items->count() !== $row_ids->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more selected PPMP items are invalid.',
            ]);
        }

        $supporting_document = $request->hasFile('supporting_document_file')
            ? $this->storeSupportingDocument($request)
            : null;

        $existing_rows->each(function ($row) use ($items, $request, $supporting_document) {
            $item = $items->get((int) data_get($row, 'id'));
            $item->fill($this->editableItemPayload($request, $row));

            if ($supporting_document) {
                $item->fill([
                    'supporting_document_path' => $supporting_document['path'],
                    'supporting_document_original_name' => $supporting_document['original_name'],
                ]);
            }

            $item->save();
        });

        if ($new_rows->isNotEmpty()) {
            $pending_status_id = $this->statusId(self::STATUS_PENDING);
            $next_item_no = $this->nextItemNumber($procurement);

            $new_rows->each(function ($row, $index) use ($procurement, $request, $pending_status_id, $supporting_document, $next_item_no) {
                ProcurementPpmpItem::query()->create($this->itemPayloadFromRequest(
                    $procurement,
                    $request,
                    $pending_status_id,
                    $next_item_no + $index,
                    $supporting_document,
                    $row
                ));
            });
        }

        $this->logPpmpActivity($procurement, $this->planShortLabelForProcurement($procurement).' item updated', [
            'item_id' => $item->id,
            'item_name' => $item->item_name,
            'item_ids' => $row_ids->all(),
        ]);

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP item updated successfully!',
            'info' => $rows->count() > 1
                ? "{$rows->count()} PPMP items were updated."
                : "{$item->item_name} was updated.",
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

        $this->ensureUserCanManagePlanItems($procurement);

        $item = ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->findOrFail($request->item_id);
        $item_name = $item->item_name;

        $item->delete();
        $this->logPpmpActivity($procurement, $this->planShortLabelForProcurement($procurement).' item deleted', [
            'item_id' => $request->item_id,
            'item_name' => $item_name,
        ]);

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP item deleted successfully!',
            'info' => "{$item_name} was removed from {$procurement->code}.",
            'status' => true,
        ];
    }

    protected function createSiblingPpmp(ProcurementPpmp $original): ProcurementPpmp
    {
        $year = $this->yearForProcurement($original);

        $payload = [
            'code' => $this->generateUnitPpmpCode($year, (int) $original->unit_id),
            'date' => $original->date,
            'purpose' => $original->purpose,
            'title' => self::PLAN_TITLE_PPMP,
            'division_id' => $original->division_id,
            'unit_id' => $original->unit_id,
            'fund_cluster_id' => $original->fund_cluster_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $original->status_id,
            'reference_app_id' => $original->reference_app_id,
        ];

        if (Schema::hasColumn('procurement_ppmps', 'ppmp_type')) {
            $payload['ppmp_type'] = $original->ppmp_type ?? self::PPMP_TYPE_INDICATIVE;
            $payload['ppmp_type_version'] = $this->nextPpmpTypeVersion($year, (int) $original->unit_id, $payload['ppmp_type']);
        }

        if (Schema::hasColumn('procurement_ppmps', 'is_supplemental')) {
            $payload['is_supplemental'] = $original->is_supplemental ?? false;
        }

        return ProcurementPpmp::query()->create($payload);
    }

    public function updateProject($id, $request): array
    {
        $original = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        $targetId = (int) $request->input('target_ppmp_id', $id);
        $procurement = $targetId !== (int) $id
            ? ProcurementPpmp::query()
                ->with(['reference_app', 'status'])
                ->where('unit_id', $original->unit_id)
                ->findOrFail($targetId)
            : $original;

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Project data can only be updated while the PPMP is still indicative.',
            ]);
        }

        $this->ensureUserCanManagePlanItems($procurement);

        $procurement->title = $request->general_description_objective;

        $projectFields = [
            'project_type', 'recommended_mode_of_procurement', 'pre_procurement_conference',
            'start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date',
            'attached_supporting_documents', 'remarks',
        ];
        foreach ($projectFields as $field) {
            if (Schema::hasColumn('procurement_ppmps', $field) && $request->filled($field)) {
                $value = $request->input($field);
                $procurement->{$field} = in_array($field, ['start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date'], true)
                    ? $this->toFullDate($value)
                    : $value;
            }
        }

        if (Schema::hasColumn('procurement_ppmps', 'project_total_budget')) {
            $procurement->project_total_budget = $request->filled('project_total_budget')
                ? (float) $request->input('project_total_budget')
                : null;
        }

        $procurement->save();

        $this->logPpmpActivity($procurement, $this->planShortLabelForProcurement($procurement).' project updated', [
            'target_ppmp_id' => $procurement->id,
        ]);

        return [
            'data' => $this->show($id),
            'message' => 'Procurement project updated successfully!',
            'status' => true,
        ];
    }

    public function clearProject($id, $request): array
    {
        $original = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        $targetId = (int) $request->input('target_ppmp_id', $id);
        $procurement = $targetId !== (int) $id
            ? ProcurementPpmp::query()
                ->with(['reference_app', 'status'])
                ->where('unit_id', $original->unit_id)
                ->findOrFail($targetId)
            : $original;

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Project data can only be cleared while the PPMP is still indicative.',
            ]);
        }

        $this->ensureUserCanManagePlanItems($procurement);

        $projectTitle = $procurement->title;

        foreach (['project_type', 'recommended_mode_of_procurement', 'pre_procurement_conference',
                  'start_of_procurement_activity', 'end_of_procurement_activity', 'expected_delivery_date',
                  'attached_supporting_documents', 'remarks', 'attachment_path', 'attachment_original_name',
                  'project_total_budget'] as $field) {
            if (Schema::hasColumn('procurement_ppmps', $field)) {
                $procurement->{$field} = null;
            }
        }

        $procurement->title = null;
        $procurement->save();

        $this->logPpmpActivity($procurement, $this->planShortLabelForProcurement($procurement).' project data cleared', [
            'previous_title' => $projectTitle,
        ]);

        return [
            'data' => $this->show($id),
            'message' => 'Procurement project removed successfully!',
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

            case $this->normalizePlanType($request->plan_type) === self::PLAN_TYPE_SPP:
                return $procurements
                    ->values()
                    ->map(function (ProcurementPpmp $procurement) {
                        $plan_names = collect([$procurement->reference_app?->name])->filter()->unique()->values();
                        $statuses = collect([$procurement->status?->name])->filter()->unique()->values();

                        $this->applyOverrides($procurement, [
                            'plan_name_override' => self::PLAN_NAME_SPP,
                            'ppmp_status_override' => $this->sppStatusForPlan($plan_names, $statuses),
                            'approval_status_override' => $this->sppStatusForPlan($plan_names, $statuses),
                        ]);

                        return $procurement;
                    });

            default:
                return $this->aggregateByUnit($procurements, $request->sort, $request->plan_type);
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

    protected function validateAppPendingSetup(?int $pending_status_id): void
    {
        if (! $pending_status_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Pending procurement status is not configured.',
            ]);
        }
    }

    protected function ensureAppCanAcceptPpmp(ProcurementApp $app, int $pending_status_id): void
    {
        if ((int) $app->status_id === (int) $pending_status_id) {
            return;
        }

        throw ValidationException::withMessages([
            'plan_type' => "{$app->code} must be Pending before PPMPs can be consolidated into the APP. Please create SPP instead",
        ]);
    }

    protected function ensurePpmpCanBeConsolidated(ProcurementPpmp $procurement, int $approved_status_id): void
    {
        $is_spp_plan = $procurement->reference_app?->name === self::PLAN_NAME_SPP;

        if ((int) $procurement->status_id === $approved_status_id && ! $procurement->reference_app_id) {
            return;
        }

        throw ValidationException::withMessages([
            'plan_type' => 'Only PPMP entries marked Submitted/For Consolidation can be consolidated.',
        ]);
    }

    protected function ensureUserCanConsolidateToApp(): void
    {
        $user = Auth::user();

        $allowed = $user && (
            $user->hasRole('BAC User')
            || $user->hasRole('BAC Chairperson')
            || $user->hasRole('BAC Vice Chairperson')
            || $user->hasRole('BAC Member')
            || in_array($user->org_chart?->designation?->name, ['BAC Chairperson', 'BAC Vice Chairperson', 'BAC Member'], true)
        );

        if ($allowed) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'Only BAC users can consolidate this plan to APP.',
        ]);
    }

    protected function ensureUserCanManagePlanItems(ProcurementPpmp $procurement): void
    {
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'item' => 'You are not allowed to manage items for this plan.',
            ]);
        }

        $user_unit_id = $user->organization?->unit_id;
        $same_unit = $user_unit_id && $procurement->unit_id
            && (int) $user_unit_id === (int) $procurement->unit_id;

        if (
            (int) $procurement->created_by_id === (int) $user->id
            || $same_unit
            || $user->hasRole('Procurement Staff')
            || $user->hasRole('Procurement Officer')
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'item' => 'Only users assigned to the same unit can manage items for this plan.',
        ]);
    }

    protected function ensurePpmpNotConsolidated(ProcurementPpmp $procurement): void
    {
        if (! $procurement->reference_app_id) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'This PPMP is already included in the APP.',
        ]);
    }

    protected function ensurePpmpHasItems(ProcurementPpmp $procurement): void
    {
        $year = $this->yearForProcurement($procurement);
        $hasProjectTypeCol = Schema::hasColumn('procurement_ppmps', 'project_type');

        // Base query: all PPMP records for the same unit/year (excluding SPP siblings)
        $baseQuery = ProcurementPpmp::query()
            ->where('unit_id', $procurement->unit_id)
            ->whereYear('date', $year)
            ->where(function ($q) {
                $q->whereNull('title')->orWhere('title', '!=', self::PLAN_NAME_SPP);
            })
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', 'NOT LIKE', 'SPP-%');
            })
            ->whereDoesntHave('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_SPP));

        // Allow if any related PPMP has line items
        if ((clone $baseQuery)->whereHas('items')->exists()) {
            return;
        }

        // Allow if any related PPMP has been saved as a project (project_type set, even without line items)
        if ($hasProjectTypeCol && (clone $baseQuery)->whereNotNull('project_type')->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'Please add at least one procurement project before updating or submitting this PPMP for review.',
        ]);
    }

    protected function transitionPolicy(ProcurementPpmp $procurement, int $current_status_id, array $status_ids): array
    {
        $user = Auth::user();

        if (! $user) {
            return [
                'allowed' => false,
                'message' => 'You are not allowed to advance this PPMP.',
            ];
        }

        // Detect plan scope the same way the rest of the service does.
        // Note: actualPlanTypeForView() returns APP / SPP / PPMP.
        $actualPlanType = $this->actualPlanTypeForView($procurement);
        $is_spp_plan = $actualPlanType === self::PLAN_TYPE_SPP;

        // Who can submit from Pending -> For Review
        $can_submit_pending =
            (int) $procurement->created_by_id === (int) $user->id
            || $user->hasRole('Procurement Staff')
            || $user->hasRole('Procurement Officer');

        // Allowed roles per status step
        $allowed = match ($current_status_id) {
            (int) $status_ids['pending'] => $can_submit_pending,
            (int) $status_ids['for_review'] => $user->hasRole('Budget Officer'),
            (int) $status_ids['reviewed'] => $user->hasRole('Procurement Officer'),
            default => false,
        };

        // Keep message generic but deterministic.
        return [
            'allowed' => (bool) $allowed,
            'message' => $is_spp_plan
                ? 'You are not allowed to update this SPP at its current status.'
                : 'You are not allowed to update this PPMP at its current status.',
        ];
    }

    // Check if the current logged-in user is allowed to move/update the PPMP status.
    protected function ensureUserCanAdvancePpmpSubmission(
        ProcurementPpmp $procurement,
        $current_status_id,
        $status_ids
    ): void
    {
        $policy = $this->transitionPolicy(
            $procurement,
            (int) $current_status_id,
            $status_ids
        );

        // If current user is not allowed,throw validation exception


        if (! $policy['allowed']) {
            throw ValidationException::withMessages([
                'ppmp' => $policy['message'],
            ]);
        }
    }


    protected function submissionStatusIds(): array
    {
        $status_ids = [
            'pending' => $this->statusId(self::STATUS_PENDING),
            'for_review' => $this->statusId(self::STATUS_FOR_REVIEW)
                ?: $this->createProcurementStatus(self::STATUS_FOR_REVIEW, 'text-info', 'bg-info'),
            'reviewed' => $this->statusId(self::STATUS_REVIEWED),
            'approved' => $this->statusId(self::STATUS_APPROVED),
        ];

        if (! $status_ids['pending'] || ! $status_ids['for_review'] || ! $status_ids['reviewed'] || ! $status_ids['approved']) {
            throw ValidationException::withMessages([
                'ppmp' => 'Pending, For Review, Reviewed, or Approved procurement status is not configured.',
            ]);
        }

        return $status_ids;
    }

    protected function currentYearAppIsApproved(int $year): bool
    {
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);

        if (! $approved_status_id) {
            return false;
        }

        if ($this->hasSeparateAppRegister()) {
            return ProcurementApp::query()
                ->where('year', $year)
                ->where('status_id', $approved_status_id)
                ->when(Schema::hasColumn('procurement_apps', 'plan_phase'), fn ($q) => $q->where('plan_phase', self::PLAN_PHASE_FINAL))
                ->exists();
        }

        return ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->where('status_id', $approved_status_id)
            ->whereHas('reference_app', fn ($q) => $q->where('name', self::PLAN_NAME_APP))
            ->exists();
    }

    protected function ensureApprovedAppExists(int $year, int $approved_status_id): void
    {
        if ($this->hasSeparateAppRegister()) {
            $has_approved_app = ProcurementApp::query()
                ->where('year', $year)
                ->where('status_id', $approved_status_id)
                ->exists();

            if (! $has_approved_app) {
                throw ValidationException::withMessages([
                    'plan_type' => 'APP must be approved for the current year before creating an SPP update.',
                ]);
            }

            return;
        }

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

    // SPP can only be created once the FINAL APP is approved (RA 9184 — supplements post-budget plan).
    protected function ensureApprovedFinalAppExists(int $year, int $approved_status_id): void
    {
        if ($this->hasSeparateAppRegister() && Schema::hasColumn('procurement_apps', 'plan_phase')) {
            $has_final_app = ProcurementApp::query()
                ->where('year', $year)
                ->where('plan_phase', self::PLAN_PHASE_FINAL)
                ->where('status_id', $approved_status_id)
                ->exists();

            if (! $has_final_app) {
                throw ValidationException::withMessages([
                    'plan_type' => 'A Supplemental Procurement Plan can only be created after the Final APP for the year is approved (RA 9184, Sec. 7).',
                ]);
            }

            return;
        }

        // Fall back to general APP check for systems without the plan_phase column
        $this->ensureApprovedAppExists($year, $approved_status_id);
    }

    protected function planPhaseLabel(string $plan_phase): string
    {
        return match ($plan_phase) {
            self::PLAN_PHASE_INDICATIVE => 'Indicative APP',
            self::PLAN_PHASE_FINAL      => 'Final APP',
            default                     => 'Annual Procurement Plan',
        };
    }

    protected function ensureAppDoesNotExist(int $year, string $plan_phase = self::PLAN_PHASE_INDICATIVE): void
    {
        if ($this->hasSeparateAppRegister()) {
            $query = ProcurementApp::query()->where('year', $year)->lockForUpdate();

            // With plan_phase column: a year can have one Indicative APP and one Final APP.
            // Without it (old schema): block any second APP for the same year.
            if (Schema::hasColumn('procurement_apps', 'plan_phase')) {
                $query->where('plan_phase', $plan_phase);
            }

            if ($query->exists()) {
                $label = $this->planPhaseLabel($plan_phase);
                throw ValidationException::withMessages([
                    'year' => "A {$label} already exists for {$year}.",
                ]);
            }

            return;
        }

        $exists = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereHas('reference_app', function ($reference_query) {
                $reference_query->where('name', self::PLAN_NAME_APP);
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'year' => 'An APP already exists for the selected year.',
            ]);
        }
    }

    protected function ensureUnitHasNoPpmpForYear(ListUnit $unit, int $year): void
    {
        $exists = ProcurementPpmp::query()
            ->where('unit_id', $unit->id)
            ->whereYear('date', $year)
            ->where(function ($query) {
                $query->whereNull('title')
                    ->orWhere('title', '!=', self::PLAN_NAME_SPP);
            })
            ->where(function ($query) {
                $query->whereNull('code')
                    ->orWhere('code', 'NOT LIKE', 'SPP-%');
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'unit_id' => 'This unit already has a PPMP for the selected year.',
            ]);
        }
    }

    protected function ensureUnitHasConsolidatedPpmpForYear(ListUnit $unit, int $year): void
    {
        $hasSeparate = $this->hasSeparateAppRegister();
        $hasPhaseCol = $hasSeparate && Schema::hasColumn('procurement_apps', 'plan_phase');

        $exists = ProcurementPpmp::query()
            ->where('unit_id', $unit->id)
            ->whereYear('date', $year)
            ->when($hasPhaseCol, fn ($q) => $q->whereHas(
                'procurement_app',
                fn ($a) => $a->where('plan_phase', self::PLAN_PHASE_FINAL)
            ), fn ($q) => $q->when($hasSeparate, fn ($inner) => $inner->whereNotNull('procurement_app_id')))
            ->whereHas('reference_app', function ($reference_query) {
                $reference_query->where('name', self::PLAN_NAME_APP);
            })
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'unit_id' => 'This unit must have a PPMP consolidated into the Final APP before creating an SPP (RA 9184, Sec. 7).',
            ]);
        }
    }

    protected function consolidatedPpmpUnitIdsForYear(int $year): Collection
    {
        return ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNotNull('unit_id')
            ->when($this->hasSeparateAppRegister(), fn ($query) => $query->whereNotNull('procurement_app_id'))
            ->whereHas('reference_app', function ($reference_query) {
                $reference_query->where('name', self::PLAN_NAME_APP);
            })
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    protected function sppPayload(int $year, ListUnit $unit, int $app_type_id, int $pending_status_id, ?int $fund_cluster_id): array
    {
        $payload = [
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
            'status_id' => $pending_status_id,
        ];

        if (Schema::hasColumn('procurement_ppmps', 'is_supplemental')) {
            $payload['is_supplemental'] = true;
        }

        return $payload;
    }

    protected function ppmpPayload(int $year, ListUnit $unit, int $pending_status_id, int $fund_cluster_id, string $ppmp_type = self::PPMP_TYPE_INDICATIVE, bool $is_supplemental = false, ?int $quarter = null): array
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

        if (Schema::hasColumn('procurement_ppmps', 'ppmp_type')) {
            $payload['ppmp_type'] = $ppmp_type;
            $payload['ppmp_type_version'] = $this->nextPpmpTypeVersion($year, (int) $unit->id, $ppmp_type, $quarter);
        }

        if (Schema::hasColumn('procurement_ppmps', 'is_supplemental')) {
            $payload['is_supplemental'] = $is_supplemental;
        }

        if (Schema::hasColumn('procurement_ppmps', 'request_id')) {
            $payload['request_id'] = $this->createPpmpRequest()->id;
        }

        if (Schema::hasColumn('procurement_ppmps', 'attachment_path')) {
            $payload['attachment_path'] = null;
            $payload['attachment_original_name'] = null;
        }

        return $payload;
    }

    protected function nextPpmpTypeVersion(int $year, int $unit_id, string $ppmp_type, ?int $quarter = null): int
    {
        $max = ProcurementPpmp::query()
            ->where('unit_id', $unit_id)
            ->whereYear('date', $year)
            ->where('ppmp_type', $ppmp_type)
            // Versions are numbered per quarter family: Q1 Indicative V1, V2... / Q2 Indicative V1, V2...
            ->when($quarter && Schema::hasColumn('procurement_ppmps', 'quarter'),
                fn ($q) => $q->where('quarter', $quarter)
            )
            ->where(function ($q) {
                $q->whereNull('title')->orWhere('title', '!=', self::PLAN_NAME_SPP);
            })
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', 'NOT LIKE', 'SPP-%');
            })
            ->max('ppmp_type_version');

        return ((int) $max) + 1;
    }

    protected function generateSppCode(int $year, int $unit_id): string
    {
        return $this->generatePlanSeriesCode('SPP-'.$year);
    }

    protected function generatePlanSeriesCode(string $base_code): string
    {
        $next_number = ProcurementPpmp::query()
            ->where('code', 'like', $base_code.'-%')
            ->pluck('code')
            ->map(function ($code) use ($base_code) {
                $suffix = str_replace($base_code.'-', '', (string) $code);

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })
            ->max() + 1;

        return $base_code.'-'.str_pad((string) max(1, $next_number), 2, '0', STR_PAD_LEFT);
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

        $payload = [
            'item_no' => $item_no,
            'procurement_ppmp_id' => $procurement->id,
            'item_unit_type_id' => data_get($row, 'item_unit_type_id', $request->item_unit_type_id),
            'item_name' => data_get($row, 'item_name', $request->item_name),
            'item_description' => data_get($row, 'item_description', $request->item_description),
            'project_type' => $request->project_type,
            'item_category_id' => data_get($row, 'item_category_id', $request->item_category_id),
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'pre_procurement_conference' => $request->pre_procurement_conference,
            'start_of_procurement_activity' => $this->toFullDate($request->start_of_procurement_activity),
            'end_of_procurement_activity' => $this->toFullDate($request->end_of_procurement_activity),
            'expected_delivery_date' => $this->toFullDate($request->expected_delivery_date),
            'attached_supporting_documents' => $request->attached_supporting_documents,
            'supporting_document_path' => $supporting_document['path'],
            'supporting_document_original_name' => $supporting_document['original_name'],
            'remarks' => $request->remarks,
            'requested_quantity' => $quantity,
            'funded_quantity' => $quantity,
            'is_partial_funding' => false,
            'item_quantity' => $quantity,
            'item_unit_cost' => $unit_cost,
            'price_basis' => null,
            'price_basis_amount' => null,
            'quantity_adjustment_reason' => null,
            'price_variance_reason' => null,
            'total_cost' => $quantity * $unit_cost,
            'q1_indicative_amount' => $this->nullableDecimal(data_get($row, 'q1_indicative_amount', $request->q1_indicative_amount)),
            'q2_indicative_amount' => $this->nullableDecimal(data_get($row, 'q2_indicative_amount', $request->q2_indicative_amount)),
            'q3_indicative_amount' => $this->nullableDecimal(data_get($row, 'q3_indicative_amount', $request->q3_indicative_amount)),
            'q4_indicative_amount' => $this->nullableDecimal(data_get($row, 'q4_indicative_amount', $request->q4_indicative_amount)),
            'status_id' => $status_id,
        ];

        if (! Schema::hasColumn('procurement_ppmp_items', 'start_of_procurement_activity')) {
            unset($payload['start_of_procurement_activity']);
        }

        $this->stripMissingItemPlanningColumns($payload);

        return $payload;
    }

    protected function editableItemPayload($request, $row = null): array
    {
        $quantity = (float) data_get($row, 'item_quantity', $request->item_quantity);
        $unit_cost = (float) data_get($row, 'item_unit_cost', $request->item_unit_cost);

        $payload = [
            'item_name' => data_get($row, 'item_name', $request->item_name),
            'item_description' => data_get($row, 'item_description', $request->item_description),
            'project_type' => $request->project_type,
            'item_category_id' => data_get($row, 'item_category_id', $request->item_category_id),
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'pre_procurement_conference' => $request->pre_procurement_conference,
            'start_of_procurement_activity' => $this->toFullDate($request->start_of_procurement_activity),
            'end_of_procurement_activity' => $this->toFullDate($request->end_of_procurement_activity),
            'expected_delivery_date' => $this->toFullDate($request->expected_delivery_date),
            'attached_supporting_documents' => $request->attached_supporting_documents,
            'remarks' => $request->remarks,
            'requested_quantity' => $quantity,
            'funded_quantity' => $quantity,
            'is_partial_funding' => false,
            'item_quantity' => $quantity,
            'item_unit_type_id' => data_get($row, 'item_unit_type_id', $request->item_unit_type_id),
            'item_unit_cost' => $unit_cost,
            'price_basis' => null,
            'price_basis_amount' => null,
            'quantity_adjustment_reason' => null,
            'price_variance_reason' => null,
            'total_cost' => $quantity * $unit_cost,
            'q1_indicative_amount' => $this->nullableDecimal(data_get($row, 'q1_indicative_amount', $request->q1_indicative_amount)),
            'q2_indicative_amount' => $this->nullableDecimal(data_get($row, 'q2_indicative_amount', $request->q2_indicative_amount)),
            'q3_indicative_amount' => $this->nullableDecimal(data_get($row, 'q3_indicative_amount', $request->q3_indicative_amount)),
            'q4_indicative_amount' => $this->nullableDecimal(data_get($row, 'q4_indicative_amount', $request->q4_indicative_amount)),
        ];

        if (! Schema::hasColumn('procurement_ppmp_items', 'start_of_procurement_activity')) {
            unset($payload['start_of_procurement_activity']);
        }

        $this->stripMissingItemPlanningColumns($payload);

        return $payload;
    }

    protected function toFullDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        // Convert YYYY-MM (from type="month" input) to YYYY-MM-01
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value.'-01';
        }

        return $value;
    }

    protected function stripMissingItemPlanningColumns(array &$payload): void
    {
        foreach ([
            'requested_quantity',
            'funded_quantity',
            'is_partial_funding',
            'price_basis',
            'price_basis_amount',
            'quantity_adjustment_reason',
            'price_variance_reason',
            'q1_indicative_amount',
            'q2_indicative_amount',
            'q3_indicative_amount',
            'q4_indicative_amount',
        ] as $column) {
            if (! Schema::hasColumn('procurement_ppmp_items', $column)) {
                unset($payload[$column]);
            }
        }
    }

    protected function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $parsed = (float) $value;

        return $parsed > 0 ? $parsed : null;
    }

    protected function itemRowsFromRequest($request): Collection
    {
        if (is_array($request->items)) {
            // Filter out any non-array entries or rows without item_name (e.g. empty FormData artifacts)
            return collect($request->items)->filter(fn ($row) => is_array($row) && filled(data_get($row, 'item_name')));
        }

        if (filled($request->item_name)) {
            return collect([[
                'item_name' => $request->item_name,
                'item_description' => $request->item_description,
                'item_quantity' => $request->item_quantity,
                'item_unit_type_id' => $request->item_unit_type_id,
                'item_unit_cost' => $request->item_unit_cost,
            ]]);
        }

        return collect([]);
    }

    protected function nextItemNumber(ProcurementPpmp $procurement): int
    {
        return ((int) ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->max('item_no')) + 1;
    }

    protected function nextSubmissionStep(int $current_status_id, int $pending_status_id, int $for_review_status_id, int $reviewed_status_id, int $approved_status_id, bool $is_app_plan = false): array
    {
        if ($is_app_plan) {
            switch ($current_status_id) {
                case $pending_status_id:
                    return [
                        'status_id' => $for_review_status_id,
                        'label' => 'For Review',
                        'message' => 'APP moved to For Review.',
                        'info' => 'The APP is now ready for review and submission.',
                    ];

                case $for_review_status_id:
                    return [
                        'status_id' => $reviewed_status_id,
                        'label' => 'Reviewed/For Submission',
                        'message' => 'APP reviewed successfully.',
                        'info' => 'The APP is now reviewed and ready for submission.',
                    ];

                case $reviewed_status_id:
                    return [
                        'status_id' => $approved_status_id,
                        'label' => 'Submitted/For Implementation',
                        'message' => 'APP submitted successfully.',
                        'info' => 'The APP is now submitted for implementation.',
                    ];

                default:
                    throw ValidationException::withMessages([
                        'ppmp' => 'Only Pending, For Review, or Reviewed APP entries can be advanced.',
                    ]);
            }
        }

        switch ($current_status_id) {
            case $pending_status_id:
                return [
                    'status_id' => $for_review_status_id,
                    'label' => 'For Review',
                    'message' => 'PPMP submitted for review successfully.',
                    'info' => 'The PPMP is now ready for review.',
                ];

            case $for_review_status_id:
                return [
                    'status_id' => $reviewed_status_id,
                    'label' => 'Reviewed/For Submission',
                    'message' => 'PPMP reviewed successfully.',
                    'info' => 'The PPMP is now ready for Procurement Officer submission.',
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
                    'ppmp' => 'Only Pending, For Review, or Reviewed PPMP entries can be advanced.',
                ]);
        }
    }

    protected function notifyNextPlanReviewers(object $plan, string $plan_type, int $next_status_id, array $status_ids): void
    {
        if (! Auth::user()) {
            return;
        }

        $plan_type = $this->planShortLabel($plan_type);
        $target_role = match ($next_status_id) {
            (int) $status_ids['for_review'] => 'Budget Officer',
            (int) $status_ids['reviewed'] => 'Procurement Officer',
            default => null,
        };

        if (! $target_role) {
            return;
        }

        $reason = $target_role === 'Budget Officer'
            ? 'plan_review_required'
            : 'plan_submission_required';

        User::query()
            ->where('is_active', 1)
            ->whereHasActiveRole($target_role)
            ->get()
            ->each(fn (User $user) => $user->notify(
                new ProcurementPlanForReviewNotification($plan, Auth::user(), $plan_type, $target_role, $reason)
            ));
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
            'submitted_by.profile',
            'reviewed_by.profile',
            'approved_by.profile',
            'consolidated_by.profile',
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

    protected function supportingDocumentTypeDropdowns(): array
    {
        $options = $this->dropdown->dropdowns('PPMP Supporting Document');

        if ($options->isNotEmpty()) {
            return $options->all();
        }

        return collect([
            'Terms of Reference',
            'Technical Specifications',
            'Project Design',
            'Program of Works',
            'Market Study',
            'Other Supporting Document',
        ])->map(fn ($name) => [
            'value' => $name,
            'name' => $name,
            'others' => 'default',
        ])->all();
    }

    protected function statusId(string $name, string $classification = 'Procurement'): ?int
    {
        $id = ListStatus::getID($name, $classification);

        return $id ? (int) $id : null;
    }

    protected function createProcurementStatus(string $name, string $color, string $bg): int
    {
        $status = ListStatus::query()->firstOrCreate(
            [
                'name' => $name,
                'classification' => 'Procurement',
            ],
            [
                'type' => 'n/a',
                'color' => $color,
                'bg' => $bg,
                'icon' => 'n/a',
                'is_active' => 1,
            ]
        );

        return (int) $status->id;
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
        if ($procurement->title === self::PLAN_NAME_SPP || $procurement->reference_app?->name === self::PLAN_NAME_SPP) {
            return false;
        }

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
        if ($plan_name === self::PLAN_NAME_APP && $this->hasSeparateAppRegister()) {
            return ProcurementApp::query()
                ->select('year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($year) => (int) $year)
                ->values()
                ->all();
        }

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

    protected function registeredAppYearsByPhase(string $plan_phase): array
    {
        if (! $this->hasSeparateAppRegister() || ! Schema::hasColumn('procurement_apps', 'plan_phase')) {
            return [];
        }

        return ProcurementApp::query()
            ->select('year')
            ->where('plan_phase', $plan_phase)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();
    }

    protected function hasSeparateAppRegister(): bool
    {
        return Schema::hasTable('procurement_apps')
            && Schema::hasColumn('procurement_ppmps', 'procurement_app_id');
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

        $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);

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
        $hasIsSupplemental = Schema::hasColumn('procurement_ppmps', 'is_supplemental');

        switch ($this->normalizePlanType($plan_type)) {
            case self::PLAN_TYPE_APP:
                $query->whereHas('reference_app', function ($reference_query) {
                    $reference_query->where('name', self::PLAN_NAME_APP);
                });
                break;

            case self::PLAN_TYPE_SPP:
                if ($hasIsSupplemental) {
                    $query->where('is_supplemental', true);
                } else {
                    $query->where(function ($spp_query) {
                        $spp_query->where('title', self::PLAN_NAME_SPP)
                            ->orWhere('code', 'LIKE', 'SPP-%')
                            ->orWhereHas('reference_app', function ($reference_query) {
                                $reference_query->where('name', self::PLAN_NAME_SPP);
                            });
                    });
                }
                break;

            case self::PLAN_TYPE_PPMP:
            default:
                if ($hasIsSupplemental) {
                    $query->where(function ($q) {
                        $q->whereNull('is_supplemental')->orWhere('is_supplemental', false);
                    });
                } else {
                    $query->where(function ($ppmp_query) {
                        $ppmp_query->whereNull('title')
                            ->orWhere('title', '!=', self::PLAN_NAME_SPP);
                    })
                        ->where(function ($ppmp_query) {
                            $ppmp_query->whereNull('code')
                                ->orWhere('code', 'NOT LIKE', 'SPP-%');
                        })
                        ->whereDoesntHave('reference_app', function ($reference_query) {
                            $reference_query->where('name', self::PLAN_NAME_SPP);
                        })
                        ->where(function ($ppmp_query) {
                            $ppmp_query->whereNull('reference_app_id')
                                ->orWhereHas('reference_app', function ($reference_query) {
                                    $reference_query->where('name', self::PLAN_NAME_APP);
                                });
                        });
                }
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

        $query = ProcurementPpmp::query()
            ->with($this->relations())
            ->withCount('comments');

        $this->applyEmployeeScope($query, $employee_unit_id);
        $this->applyKeywordSearch($query, $request->keyword);
        $this->applyListFilters($query, $request, $employee_unit_id);
        $this->applySort($query, $request->sort);

        return $query;
    }

    protected function appLists($request)
    {
        $per_page = (int) ($request->count ?? 10);
        $query = ProcurementApp::query()
            ->with($this->appRelations())
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($keyword_query) use ($keyword) {
                    $keyword_query->where('code', 'like', "%{$keyword}%")
                        ->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('year', 'like', "%{$keyword}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->whereHas('status', fn ($status_query) => $status_query->where('name', $status));
            });

        match ($request->sort) {
            'oldest' => $query->orderBy('year')->orderBy('version')->orderBy('created_at'),
            default => $query->orderByDesc('year')->orderByDesc('version')->orderByDesc('created_at'),
        };

        $apps = $query->paginate($per_page);
        $apps->setCollection($apps->getCollection()->map(fn (ProcurementApp $app) => $this->appResource($app)));

        return $apps;
    }

    protected function appRelations(): array
    {
        return [
            'app_type',
            'created_by.profile',
            'requested_by.profile',
            'submitted_by.profile',
            'reviewed_by.profile',
            'approved_by.profile',
            'comments.user.profile',
            'status',
            'source_ppmps' => fn ($query) => $query
                ->with($this->relations())
                ->withCount('comments')
                ->orderBy('unit_id')
                ->orderBy('code'),
        ];
    }

    protected function appResource(ProcurementApp $app): array
    {
        $source_ppmps = $app->source_ppmps ?? collect();
        $resource = $this->aggregateAgencyWide($source_ppmps, self::PLAN_NAME_APP)->first();
        $display_number = $this->appDisplayNumber($app);

        if ($resource) {
            $resource->status_id = $app->status_id;
            $resource->setRelation('status', $app->status);
            $resource->approved_by_id = $app->approved_by_id;
            $resource->setRelation('approved_by', $app->approved_by);

            $this->applyOverrides($resource, [
                'ppmp_no_override' => $display_number,
                'plan_name_override' => self::PLAN_NAME_APP,
                'ppmp_status_override' => $this->appStatusLabel(collect([$app->status?->name])->filter()),
                'approval_status_override' => $this->appStatusLabel(collect([$app->status?->name])->filter()),
                'source_ppmps_override' => $this->sourcePpmpSummaries($source_ppmps, self::PLAN_NAME_APP),
                'start_date_override' => $app->year.'-01-01',
            ]);

            $data = (new ProcurementPPMPResource($resource))->resolve();
        } else {
            $data = [
                'estimated_budget' => 0,
                'items_count' => 0,
                'item_details' => [],
                'raw_item_details' => [],
                'source_ppmps' => [],
            ];
        }

        $plan_phase = $app->plan_phase ?? self::PLAN_PHASE_FINAL;

        return array_merge($data, [
            'id' => $app->id,
            'code' => $app->code,
            'version' => (int) ($app->version ?? 1),
            'plan_phase' => $plan_phase,
            'plan_phase_label' => $this->planPhaseLabel($plan_phase),
            'pr_no' => $app->code,
            'ppmp_no' => $display_number,
            'ppmp_status' => $this->appStatusLabel(collect([$app->status?->name])->filter()),
            'approval_status' => $this->appStatusLabel(collect([$app->status?->name])->filter()),
            'plan_name' => self::PLAN_NAME_APP,
            'plan_type' => 'annual',
            'date' => $app->year.'-01-01',
            'start_of_procurement_activity' => $app->year.'-01-01',
            'unit' => [
                'id' => null,
                'name' => 'Agency-wide',
                'short' => 'APP',
            ],
            'created_by_id' => $app->created_by_id,
            'created_by' => $app->created_by?->profile?->full_name,
            'approved_by' => $app->approved_by?->profile?->full_name,
            'reviewed_by' => in_array($app->status?->name, [self::STATUS_REVIEWED, self::STATUS_APPROVED], true)
                ? $app->reviewed_by?->profile?->full_name
                : null,
            'reviewed_at' => in_array($app->status?->name, [self::STATUS_REVIEWED, self::STATUS_APPROVED], true)
                ? $app->updated_at
                : null,
            'comments' => $app->comments ?? [],
            'comments_count' => (int) ($app->comments_count ?? $app->comments?->count() ?? 0),
            'can_submit_final' => $this->can_advance_app_status($app),
            'can_approve_to_app' => false,
            'can_revert_status' => $this->canRevertPlanStatus($app->status_id),
            'is_final' => true,
        ]);
    }

    protected function appDisplayNumber(ProcurementApp $app): string
    {
        if (preg_match('/^APP-\d{4}-(\d{2})/', (string) $app->code, $matches)) {
            return $matches[1];
        }

        return str_pad((string) ((int) ($app->version ?? 1)), 2, '0', STR_PAD_LEFT);
    }

    protected function planDisplayNumber(?string $number): ?string
    {
        if (! $number) {
            return $number;
        }

        return preg_match('/-(\d+)$/', $number, $matches)
            ? str_pad((string) ((int) $matches[1]), 2, '0', STR_PAD_LEFT)
            : $number;
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
                    ->where(function ($spp_query) {
                        $spp_query->where('title', self::PLAN_NAME_SPP)
                            ->orWhere('code', 'LIKE', 'SPP-%')
                            ->orWhereHas('reference_app', function ($reference_query) {
                                $reference_query->where('name', self::PLAN_NAME_SPP);
                            });
                    });

            default:
                return $query->where('unit_id', $procurement->unit_id)
                    ->whereYear('date', $year)
                    ->where(function ($ppmp_query) {
                        $ppmp_query->whereNull('title')
                            ->orWhere('title', '!=', self::PLAN_NAME_SPP);
                    })
                    ->where(function ($ppmp_query) {
                        $ppmp_query->whereNull('code')
                            ->orWhere('code', 'NOT LIKE', 'SPP-%');
                    })
                    ->whereDoesntHave('reference_app', function ($reference_query) {
                        $reference_query->where('name', self::PLAN_NAME_SPP);
                    })
                    ->where(function ($ppmp_query) {
                        $ppmp_query->whereNull('reference_app_id')
                            ->orWhereHas('reference_app', function ($reference_query) {
                                $reference_query->where('name', self::PLAN_NAME_APP);
                            });
                    });
        }
    }

    protected function aggregateByUnit(Collection $procurements, ?string $sort = null, ?string $plan_type = null): Collection
    {
        $plan_type = $this->normalizePlanType($plan_type);
        $hasProjectTypeCol = Schema::hasColumn('procurement_ppmps', 'project_type');

        return $procurements
            ->groupBy(fn (ProcurementPpmp $p) => $p->unit_id . '||' . ($p->ppmp_type ?? self::PPMP_TYPE_INDICATIVE) . '||' . ($p->quarter ?? 0))
            ->map(function (Collection $unit_procurements) use ($plan_type, $hasProjectTypeCol) {
                $current_user_id = Auth::id();
                $representative = $unit_procurements
                    ->sortByDesc(fn ($procurement) => (int) (
                        $procurement->status?->name === self::STATUS_PENDING
                        && (int) $procurement->created_by_id === (int) $current_user_id
                    ))
                    ->first();
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
                    case $plan_type === self::PLAN_TYPE_PPMP:
                        $plan_name = null;
                        break;

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

                $no_item_ppmps = $hasProjectTypeCol
                    ? $unit_procurements->filter(fn ($p) => ($p->items ?? collect())->isEmpty() && ! is_null($p->project_type))->values()
                    : collect();

                $representative->setRelations([
                    'items' => $items,
                    'codes' => $codes,
                    'no_item_ppmps' => $no_item_ppmps,
                ]);

                $this->applyOverrides($representative, [
                    'ppmp_no_override' => $representative->code ?: $number_prefix.'-'.$year.'-01',
                    'pr_no_override' => $pr_nos->implode(', '),
                    'plan_name_override' => $plan_type === self::PLAN_TYPE_SPP ? self::PLAN_NAME_SPP : $plan_name,
                    'ppmp_status_override' => $plan_type === self::PLAN_TYPE_SPP
                        ? $this->sppStatusForPlan($plan_names, $statuses)
                        : $this->ppmpStatusForGroup($plan_names, $statuses),
                    'aggregated_ppmp_count' => $unit_procurements->count(),
                    'classification_override' => $classification_names->implode(', '),
                    'source_of_funds_override' => $fund_sources->implode(', '),
                    'start_date_override' => $unit_procurements->pluck('date')->filter()->sort()->first(),
                    'approval_status_override' => $plan_type === self::PLAN_TYPE_SPP
                        ? $this->sppStatusForPlan($plan_names, $statuses)
                        : $this->approvalStatusForGroup($plan_names, $statuses),
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
            'ppmp_no_override' => $representative->code ?: $plan_short.'-'.$year.'-01',
            'pr_no_override' => $pr_nos->implode(', '),
            'plan_name_override' => $plan_name,
            'ppmp_status_override' => $this->ppmpStatusForGroup($plan_names, $statuses, $plan_name),
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
                $approval_status = 'Submitted/For Consolidation';
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
                $plan_type = $this->actualPlanTypeForView($procurement);

                return [
                    'id' => $procurement->id,
                    'plan_type' => $plan_type,
                    'ppmp_no' => $this->planDisplayNumber(
                        $procurement->code ?: $plan_type.'-'.$year.'-'.str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT)
                    ),
                    'unit_id' => $procurement->unit_id,
                    'unit' => $procurement->unit?->name,
                    'division' => $procurement->division,
                    'pr_no' => $procurement->code,
                    'items_count' => $items->count(),
                    'total_amount' => $items->sum(function ($item) {
                        $stored = (float) ($item->total_cost ?? 0);
                        return $stored ?: ((float) ($item->item_quantity ?? 0) * (float) ($item->item_unit_cost ?? 0));
                    }),
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

    protected function consolidationSnapshot(ProcurementPpmp $procurement, ?int $app_id): array
    {
        $year = $this->yearForProcurement($procurement);
        $plans = ProcurementPpmp::query()
            ->with([
                'unit',
                'reference_app',
                'procurement_app',
                'items.item_unit_type',
                'items.item_category',
                'items.status',
            ])
            ->whereYear('date', $year)
            ->when(
                $app_id,
                fn ($query) => $query->where('procurement_app_id', $app_id),
                fn ($query) => $query->whereHas(
                    'reference_app',
                    fn ($reference_query) => $reference_query->where('name', self::PLAN_NAME_APP)
                )
            )
            ->get();

        $source_items = $plans
            ->flatMap(function (ProcurementPpmp $plan) use ($procurement) {
                return $plan->items->map(function (ProcurementPpmpItem $item) use ($plan, $procurement) {
                    $quantity = (float) ($item->item_quantity ?? 0);
                    $unit_cost = (float) ($item->item_unit_cost ?? 0);
                    $abc = (float) ($item->total_cost ?? ($quantity * $unit_cost));

                    return [
                        'source_plan_id' => $plan->id,
                        'source_plan_code' => $plan->code,
                        'source_plan_type' => $this->planShortLabelForProcurement($plan),
                        'source_unit_id' => $plan->unit_id,
                        'source_unit' => $plan->unit?->name,
                        'is_current_consolidation' => (int) $plan->id === (int) $procurement->id,
                        'item_id' => $item->id,
                        'item_no' => $item->item_no,
                        'item_name' => $item->item_name,
                        'item_description' => $item->item_description,
                        'item_category_id' => $item->item_category_id,
                        'item_category' => $item->item_category?->name,
                        'item_unit_type_id' => $item->item_unit_type_id,
                        'item_unit_type' => $item->item_unit_type?->name,
                        'project_type' => $item->project_type,
                        'recommended_mode_of_procurement' => $item->recommended_mode_of_procurement,
                        'pre_procurement_conference' => $item->pre_procurement_conference,
                        'start_of_procurement_activity' => $item->start_of_procurement_activity,
                        'end_of_procurement_activity' => $item->end_of_procurement_activity,
                        'expected_delivery_date' => $item->expected_delivery_date,
                        'attached_supporting_documents' => $item->attached_supporting_documents,
                        'supporting_document_path' => $item->supporting_document_path,
                        'supporting_document_original_name' => $item->supporting_document_original_name,
                        'remarks' => $item->remarks,
                        'requested_quantity' => round((float) ($item->requested_quantity ?? $quantity), 2),
                        'funded_quantity' => round((float) ($item->funded_quantity ?? $quantity), 2),
                        'is_partial_funding' => (bool) $item->is_partial_funding,
                        'quantity' => round($quantity, 2),
                        'unit_cost' => round($unit_cost, 2),
                        'abc' => round($abc, 2),
                        'status_id' => $item->status_id,
                        'status' => $item->status?->name,
                    ];
                });
            })
            ->values();

        $consolidated_items = $source_items
            ->groupBy(fn (array $item) => $this->consolidationSnapshotGroupKey($item))
            ->values()
            ->map(function (Collection $items, int $index) {
                $representative = $items->first();
                $quantity = $items->sum('quantity');
                $abc = $items->sum('abc');
                $prices = $items->pluck('unit_cost')->map(fn ($price) => (float) $price);
                $minimum_price = (float) ($prices->min() ?? 0);
                $maximum_price = (float) ($prices->max() ?? 0);

                return [
                    'group_no' => $index + 1,
                    'source_item_ids' => $items->pluck('item_id')->values()->all(),
                    'source_plan_codes' => $items->pluck('source_plan_code')->filter()->unique()->values()->all(),
                    'item_name' => $representative['item_name'],
                    'item_description' => $representative['item_description'],
                    'item_category_id' => $representative['item_category_id'],
                    'item_category' => $representative['item_category'],
                    'item_unit_type_id' => $representative['item_unit_type_id'],
                    'item_unit_type' => $representative['item_unit_type'],
                    'project_type' => $representative['project_type'],
                    'quantity' => round((float) $quantity, 2),
                    'weighted_unit_cost' => $quantity > 0 ? round((float) ($abc / $quantity), 2) : 0,
                    'minimum_unit_cost' => round($minimum_price, 2),
                    'maximum_unit_cost' => round($maximum_price, 2),
                    'price_spread_percentage' => $minimum_price > 0
                        ? round((($maximum_price - $minimum_price) / $minimum_price) * 100, 2)
                        : null,
                    'abc' => round((float) $abc, 2),
                ];
            })
            ->all();

        return [
            'schema_version' => 1,
            'captured_at' => now()->toISOString(),
            'captured_by_id' => Auth::id(),
            'year' => $year,
            'app_id' => $app_id,
            'app_code' => $procurement->procurement_app?->code,
            'consolidated_plan_id' => $procurement->id,
            'consolidated_plan_code' => $procurement->code,
            'source_plan_count' => $plans->count(),
            'source_item_count' => $source_items->count(),
            'consolidated_item_count' => count($consolidated_items),
            'total_abc' => round((float) $source_items->sum('abc'), 2),
            'source_items' => $source_items->all(),
            'consolidated_items' => $consolidated_items,
        ];
    }

    protected function consolidationSnapshotGroupKey(array $item): string
    {
        return implode('|', [
            $item['item_category_id'] ?? '',
            $item['item_unit_type_id'] ?? '',
            $this->normalizeConsolidationSnapshotText($item['project_type'] ?? ''),
            $this->normalizeConsolidationSnapshotText($item['item_name'] ?? ''),
            $this->normalizeConsolidationSnapshotText($item['item_description'] ?? ''),
        ]);
    }

    protected function normalizeConsolidationSnapshotText($value): string
    {
        $text = html_entity_decode(strip_tags(strtolower((string) $value)));
        $text = preg_replace('/[^a-z0-9.\s-]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', (string) $text);

        return trim((string) $text);
    }

    protected function logPpmpActivity(ProcurementPpmp $procurement, string $description, array $properties = []): void
    {
        $logger = activity('Procurement Plan')
            ->performedOn($procurement)
            ->withProperties(array_filter(array_merge([
                'plan_id' => $procurement->id,
                'plan_code' => $procurement->code,
                'plan_type' => $properties['plan_type'] ?? $this->planShortLabelForProcurement($procurement),
                'unit_id' => $procurement->unit_id,
            ], $properties), fn ($value) => $value !== null));

        if (Auth::user()) {
            $logger->causedBy(Auth::user());
        }

        $logger->log($description);
    }

    protected function logAppActivity(ProcurementApp $app, string $description, array $properties = []): void
    {
        $logger = activity('APP')
            ->performedOn($app)
            ->withProperties(array_filter(array_merge([
                'plan_id' => $app->id,
                'plan_code' => $app->code,
                'plan_type' => self::PLAN_TYPE_APP,
                'year' => $app->year,
            ], $properties), fn ($value) => $value !== null));

        if (Auth::user()) {
            $logger->causedBy(Auth::user());
        }

        $logger->log($description);
    }

    protected function planShortLabel(?string $plan_type): string
    {
        return match ($this->normalizePlanType($plan_type)) {
            self::PLAN_TYPE_APP => self::PLAN_TYPE_APP,
            self::PLAN_TYPE_SPP => self::PLAN_TYPE_SPP,
            default => self::PLAN_TYPE_PPMP,
        };
    }

    protected function planShortLabelForProcurement(ProcurementPpmp $procurement): string
    {
        return match ($this->actualPlanTypeForView($procurement)) {
            self::PLAN_TYPE_APP => self::PLAN_TYPE_APP,
            self::PLAN_TYPE_SPP => self::PLAN_TYPE_SPP,
            default => self::PLAN_TYPE_PPMP,
        };
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
                return $this->sppStatusLabel($statuses);

            case $plan_name === self::PLAN_NAME_APP:
                return $this->appStatusLabel($statuses);

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
                return true;

            default:
                return false;
        }
    }

    protected function actualPlanTypeForView(ProcurementPpmp $procurement): string
    {
        if ($this->isSppProcurement($procurement)) {
            return self::PLAN_TYPE_SPP;
        }

        switch ($procurement->reference_app?->name) {
            case self::PLAN_NAME_APP:
                return self::PLAN_TYPE_APP;

            case self::PLAN_NAME_SPP:
                return self::PLAN_TYPE_SPP;

            default:
                return self::PLAN_TYPE_PPMP;
        }
    }

    protected function isSppProcurement(ProcurementPpmp $procurement): bool
    {
        if (Schema::hasColumn('procurement_ppmps', 'is_supplemental')) {
            return (bool) $procurement->is_supplemental;
        }

        return $procurement->title === self::PLAN_NAME_SPP
            || str_starts_with((string) $procurement->code, 'SPP-')
            || $procurement->reference_app?->name === self::PLAN_NAME_SPP;
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
            case $plan_names->contains(self::PLAN_NAME_SPP) && ! $plan_names->contains(self::PLAN_NAME_APP):
                return $this->sppStatusLabel($statuses);

            case $plan_names->contains(self::PLAN_NAME_APP) && ! $plan_names->contains(self::PLAN_NAME_SPP):
                return 'Consolidated/Added to APP';

            case $plan_names->isNotEmpty():
                return 'Consolidated/Added to APP and SPP';

            case $statuses->contains(self::STATUS_APPROVED):
                return 'Submitted/For Consolidation';

            case $statuses->contains(self::STATUS_REVIEWED):
                return 'Reviewed/For Submission';

            case $statuses->contains(self::STATUS_FOR_REVIEW):
                return 'For Review';

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

    protected function ppmpStatusForGroup(Collection $plan_names, Collection $statuses, ?string $plan_name = null): string
    {
        if ($plan_name === self::PLAN_NAME_SPP && $plan_names->contains(self::PLAN_NAME_SPP)) {
            return $this->sppStatusLabel($statuses);
        }

        if ($plan_name === self::PLAN_NAME_APP && $plan_names->contains(self::PLAN_NAME_APP)) {
            return $this->appStatusLabel($statuses);
        }

        switch (true) {
            case $plan_names->contains(self::PLAN_NAME_SPP) && ! $plan_names->contains(self::PLAN_NAME_APP):
                return $this->sppStatusLabel($statuses);

            case $plan_names->isNotEmpty():
                return 'Consolidated/Added to APP';

            case $statuses->contains(self::STATUS_APPROVED):
                return 'Submitted/For Consolidation';

            case $statuses->contains(self::STATUS_REVIEWED):
                return 'Reviewed/For Submission';

            case $statuses->contains(self::STATUS_FOR_REVIEW):
                return 'For Review';

            default:
                return 'Pending';
        }
    }

    protected function appStatusLabel(Collection $statuses): string
    {
        return match (true) {
            $statuses->contains(self::STATUS_APPROVED) => 'Submitted/For Implementation',
            $statuses->contains(self::STATUS_REVIEWED) => 'Reviewed/For Submission',
            $statuses->contains(self::STATUS_FOR_REVIEW) => 'For Review',
            $statuses->contains(self::STATUS_PENDING) => 'Pending',
            default => 'Pending',
        };
    }

    protected function sppStatusLabel(Collection $statuses): string
    {
        return match (true) {
            $statuses->contains(self::STATUS_APPROVED) => 'Submitted/For Consolidation',
            $statuses->contains(self::STATUS_REVIEWED) => 'Reviewed/For Submission',
            $statuses->contains(self::STATUS_FOR_REVIEW) => 'For Review',
            $statuses->contains(self::STATUS_PENDING) => 'Pending',
            default => 'Pending',
        };
    }

    protected function sppStatusForPlan(Collection $plan_names, Collection $statuses): string
    {
        if ($plan_names->contains(self::PLAN_NAME_APP)) {
            return 'Consolidated/Added to APP';
        }

        return $this->sppStatusLabel($statuses);
    }
}
