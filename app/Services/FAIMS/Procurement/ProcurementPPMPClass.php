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
            default => abort(404),
        };
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
                'supporting_document_types' => $this->supportingDocumentTypeDropdowns(),
            ],
        ];
    }

    public function availablePpmpUnits($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $employee_unit_id = $this->employeeOnlyUnitId();
        $used_unit_ids = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNotNull('unit_id')
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return ListUnit::query()
            ->where('is_active', 1)
            ->when($employee_unit_id, fn ($query, $unit_id) => $query->where('id', $unit_id))
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

    public function availableSppUnits($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $employee_unit_id = $this->employeeOnlyUnitId();
        // A unit can create multiple SPPs. Eligibility depends only on having a PPMP already consolidated into the APP.
        $unit_ids = $this->consolidatedPpmpUnitIdsForYear($year);

        return ListUnit::query()
            ->where('is_active', 1)
            ->whereIn('id', $unit_ids)
            ->when($employee_unit_id, fn ($query, $unit_id) => $query->where('id', $unit_id))
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
        $this->ensureApprovedAppExists($year, $approved_status_id);
        $this->ensureUnitHasConsolidatedPpmpForYear($unit, $year);

        $procurement = ProcurementPpmp::query()->create($this->sppPayload(
            $year,
            $unit,
            $app_type_id,
            $pending_status_id,
            $fund_cluster_id
        ));
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
        $app_type_id = ListDropdown::getID(self::PLAN_NAME_APP, 'APP Type');
        $approved_status_id = $this->statusId(self::STATUS_APPROVED);
        $pending_status_id = $this->statusId(self::STATUS_PENDING);

        $this->validateAppSetup($app_type_id, $approved_status_id);
        $this->validateAppPendingSetup($pending_status_id);
        $this->ensureUserCanCreateApp();
        $this->ensureAppDoesNotExist($year);

        if ($this->hasSeparateAppRegister()) {
            return $this->createSeparateApp($year, $app_type_id, $approved_status_id, $pending_status_id);
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

        $this->ensureUserCanCreatePlanForUnit($unit);
        $this->validatePpmpSetup($pending_status_id, $fund_cluster_id);
        $this->ensureUnitHasNoPpmpForYear($unit, $year);

        $procurement = ProcurementPpmp::query()->create($this->ppmpPayload(
            $year,
            $unit,
            $pending_status_id,
            $fund_cluster_id
        ));
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

    protected function createSeparateApp(int $year, int $app_type_id, int $approved_status_id, int $pending_status_id): array
    {
        $source_query = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNull('reference_app_id')
            ->whereNull('procurement_app_id')
            ->where('status_id', $approved_status_id);

        $app = ProcurementApp::query()->create([
            'code' => $this->generateAppCode($year, 1),
            'year' => $year,
            'version' => 1,
            'title' => self::PLAN_NAME_APP,
            'app_type_id' => $app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $pending_status_id,
        ]);

        $updated = $source_query->update([
            'reference_app_id' => $app_type_id,
            'procurement_app_id' => $app->id,
            'updated_at' => now(),
        ]);

        $info = $updated
            ? "{$updated} PPMP ".($updated === 1 ? 'entry was' : 'entries were')." linked to {$app->code}."
            : "{$app->code} was created for {$year}. PPMPs can be consolidated into it once they are ready.";
        $this->logAppActivity($app, 'APP created', [
            'plan_type' => self::PLAN_TYPE_APP,
            'year' => $year,
            'affected_ppmps' => $updated,
        ]);

        return [
            'data' => $this->appResource($app->fresh($this->appRelations())),
            'message' => 'APP created successfully!',
            'info' => $info,
            'status' => true,
        ];
    }

    protected function createUpdatedAppVersion(ProcurementApp $previous_app, int $pending_status_id): ProcurementApp
    {
        $next_version = ((int) ProcurementApp::query()
            ->where('year', $previous_app->year)
            ->max('version')) + 1;

        $app = ProcurementApp::query()->create([
            'code' => $this->generateAppVersionCode($previous_app, $next_version),
            'year' => $previous_app->year,
            'version' => $next_version,
            'title' => $previous_app->title ?: self::PLAN_NAME_APP,
            'app_type_id' => $previous_app->app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'status_id' => $pending_status_id,
        ]);

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
            $updates['submitted_by_id'] = Auth::id();
        }

        if ($next_step['status_id'] === $status_ids['reviewed']) {
            $updates['reviewed_by_id'] = Auth::id();
        }

        if ($next_step['status_id'] === $status_ids['approved']) {
            $updates['approved_by_id'] = Auth::id();
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
        $procurement = ProcurementPpmp::with(['status', 'reference_app'])->findOrFail($id);

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

            // Regular PPMP can only be added while APP is still pending.
            // Approved SPPs become a new APP update/version once the previous APP is already for implementation.
            if (
                $is_spp_plan
                && (int) $app->status_id === (int) $approved_status_id
                && $this->appHasConsolidatedPpmpSources($app, $app_type_id)
            ) {
                $app = $this->createUpdatedAppVersion($app, $pending_status_id);
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

        $procurement = ProcurementPpmp::with($this->relations())->findOrFail($id);
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
            // PPMP/SPP should update only the selected record
            $procurement->update($updates);
            $updated = 1;
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
            ->findOrFail($id);

        if ($this->isLockedForItemChanges($procurement)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be added while the PPMP is still indicative.',
            ]);
        }

        $this->ensureUserCanManagePlanItems($procurement);

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
        $this->logPpmpActivity($procurement, $this->planShortLabelForProcurement($procurement).' item added', [
            'items_count' => $created_items->count(),
            'item_ids' => $created_items->pluck('id')->values()->all(),
        ]);

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

        $this->ensureUserCanManagePlanItems($procurement);

        $item = ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->findOrFail($request->item_id);

        $procurement->title = $request->general_description_objective;
        $procurement->save();

        $rows = $this->itemRowsFromRequest($request);
        $row_ids = $rows->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();

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

        $rows->each(function ($row) use ($items, $request, $supporting_document) {
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

        if ($procurement->status_id === $approved_status_id && (! $procurement->reference_app_id || $is_spp_plan)) {
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
        if ($procurement->items()->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'ppmp' => 'Please add at least one item before updating or submitting this PPMP for review.',
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

    protected function ensureAppDoesNotExist(int $year): void
    {
        if ($this->hasSeparateAppRegister()) {
            $exists = ProcurementApp::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'year' => 'An APP already exists for the selected year.',
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
        $exists = ProcurementPpmp::query()
            ->where('unit_id', $unit->id)
            ->whereYear('date', $year)
            ->when($this->hasSeparateAppRegister(), fn ($query) => $query->whereNotNull('procurement_app_id'))
            ->whereHas('reference_app', function ($reference_query) {
                $reference_query->where('name', self::PLAN_NAME_APP);
            })
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'unit_id' => 'This unit must have a PPMP consolidated/added to APP before creating an SPP.',
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
            'status_id' => $pending_status_id,
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
            'item_category_id' => $request->item_category_id,
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'pre_procurement_conference' => $request->pre_procurement_conference,
            'start_of_procurement_activity' => $request->start_of_procurement_activity,
            'end_of_procurement_activity' => $request->end_of_procurement_activity,
            'expected_delivery_date' => $request->expected_delivery_date,
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
            'item_category_id' => $request->item_category_id,
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'pre_procurement_conference' => $request->pre_procurement_conference,
            'start_of_procurement_activity' => $request->start_of_procurement_activity,
            'end_of_procurement_activity' => $request->end_of_procurement_activity,
            'expected_delivery_date' => $request->expected_delivery_date,
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
        ];

        if (! Schema::hasColumn('procurement_ppmp_items', 'start_of_procurement_activity')) {
            unset($payload['start_of_procurement_activity']);
        }

        $this->stripMissingItemPlanningColumns($payload);

        return $payload;
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
        ] as $column) {
            if (! Schema::hasColumn('procurement_ppmp_items', $column)) {
                unset($payload[$column]);
            }
        }
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
                $query->where(function ($spp_query) {
                    $spp_query->where('title', self::PLAN_NAME_SPP)
                        ->orWhere('code', 'LIKE', 'SPP-%')
                        ->orWhereHas('reference_app', function ($reference_query) {
                            $reference_query->where('name', self::PLAN_NAME_SPP);
                        });
                });
                break;

            case self::PLAN_TYPE_PPMP:
            default:
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

        return array_merge($data, [
            'id' => $app->id,
            'code' => $app->code,
            'version' => (int) ($app->version ?? 1),
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

        return $procurements
            ->groupBy('unit_id')
            ->map(function (Collection $unit_procurements) use ($plan_type) {
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

                $representative->setRelations([
                    'items' => $items,
                    'codes' => $codes,
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
