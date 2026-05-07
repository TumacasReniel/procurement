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
    public function __construct(protected DropdownClass $dropdown)
    {
    }

    public function store($request): array
    {
        $this->ensureCanManage();

        return match ($request->option) {
            'create_unit_ppmp' => $this->createUnitPpmp($request),
            default => $this->storeAppPlan($request),
        };
    }

    public function updateByOption($id, $request): array
    {
        return match ($request->option) {
            'submit_final' => $this->markFinal($id, $request),
            'add_item' => $this->addItem($id, $request),
            default => abort(404),
        };
    }

    protected function storeAppPlan($request): array
    {
        if ($request->plan_type === 'supplemental') {
            $this->ensureCanCreateSpp();
        }

        return $this->createPlan($request);
    }

    protected function markFinal($id, $request): array
    {
        $this->ensureCanMarkFinalPpmp();

        return $this->submitFinal($id, $request);
    }

    public function lists($request)
    {
        $per_page = (int) ($request->count ?: 10);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $procurements = $this->ppmp_query($request)->get();
        $grouped = in_array($request->plan_type, ['annual', 'supplemental'], true)
            ? $this->aggregate_agency_wide_by_year($procurements, $this->plan_name_for_type($request->plan_type))
            : $this->aggregate_by_unit($procurements, $request->sort);
        $page_items = $grouped->forPage($page, $per_page)->values();

        return ProcurementPPMPResource::collection(
            new LengthAwarePaginator(
                $page_items,
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

    public function index_page_props(): array
    {
        return [
            'dropdowns' => [
                'roles' => Auth::user()->roles,
                'designation' => Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'units' => $this->dropdown->list_units(),
                'unit_types' => $this->dropdown->unit_types(),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'mode_of_procurements' => $this->dropdown->dropdowns('Mode of Procurement'),
                'app_types' => $this->dropdown->dropdowns('APP Type'),
                'annual_app_years' => $this->registered_plan_years('Annual Procurement Plan'),
            ],
        ];
    }

    public function indexPageProps(): array
    {
        return $this->index_page_props();
    }

    public function show_page_props($id, $request = null): array
    {
        return [
            'ppmp' => $this->show($id, $request),
            'dropdowns' => [
                'unit_types' => $this->dropdown->unit_types(),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'mode_of_procurements' => $this->dropdown->dropdowns('Mode of Procurement'),
            ],
        ];
    }

    public function showPageProps($id, $request = null): array
    {
        return $this->show_page_props($id, $request);
    }

    public function available_ppmp_units($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $used_unit_ids = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNull('reference_app_id')
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

    public function availablePpmpUnits($request): array
    {
        return $this->available_ppmp_units($request);
    }

    public function show($id, $request = null): array
    {
        $procurement = ProcurementPpmp::query()
            ->with($this->relations())
            ->findOrFail($id);

        $plan_type = data_get($request, 'plan_type');
        $procurements = $this->aggregate_source_query($procurement, $plan_type)->get();
        $plan_name = $plan_type === 'annual'
            ? 'Annual Procurement Plan'
            : ($plan_type === 'supplemental' ? 'Supplemental Procurement Plan' : $procurement->reference_app?->name);

        $resource = in_array($plan_name, ['Annual Procurement Plan', 'Supplemental Procurement Plan'], true)
            ? $this->aggregate_agency_wide($procurements, $plan_name)->first()
            : $this->aggregate_by_unit($procurements)->first();

        return (new ProcurementPPMPResource($resource ?: $procurement))->resolve();
    }

    public function create_plan($request): array
    {
        if ($request->plan_type === 'supplemental') {
            return $this->create_spp_item_plan($request);
        }

        $app_type_name = $request->plan_type === 'supplemental'
            ? 'Supplemental Procurement Plan'
            : 'Annual Procurement Plan';

        $app_type_id = ListDropdown::getID($app_type_name, 'APP Type');

        if (!$app_type_id) {
            throw ValidationException::withMessages([
                'plan_type' => "{$app_type_name} is not configured in APP Type dropdowns.",
            ]);
        }

        $reviewed_status_id = ListStatus::getID('Reviewed', 'Procurement');
        $approved_status_id = ListStatus::getID('Approved', 'Procurement');

        if (!$reviewed_status_id || !$approved_status_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Reviewed or Approved procurement status is not configured.',
            ]);
        }

        if ($request->plan_type === 'annual') {
            $annual_app_exists = ProcurementPpmp::query()
                ->whereYear('date', $request->year)
                ->where('status_id', $approved_status_id)
                ->whereHas('reference_app', function ($reference_query) {
                    $reference_query->where('name', 'Annual Procurement Plan');
                })
                ->exists();

            if ($annual_app_exists) {
                throw ValidationException::withMessages([
                    'year' => 'An APP already exists for the selected year.',
                ]);
            }
        }

        $query = ProcurementPpmp::query()
            ->whereYear('date', $request->year)
            ->where('status_id', $reviewed_status_id);

        if ($request->plan_type === 'annual') {
            $query->where(function ($annual_query) {
                $annual_query->whereNull('reference_app_id')
                    ->orWhereHas('reference_app', function ($reference_query) {
                        $reference_query->where('name', 'Annual Procurement Plan');
                    });
            });
        } else {
            $has_approved_app = ProcurementPpmp::query()
                ->whereYear('date', $request->year)
                ->where('status_id', $approved_status_id)
                ->whereHas('reference_app', function ($reference_query) {
                    $reference_query->where('name', 'Annual Procurement Plan');
                })
                ->exists();

            if (!$has_approved_app) {
                throw ValidationException::withMessages([
                    'plan_type' => 'APP must be approved for the selected year before creating an SPP update.',
                ]);
            }

            $query->whereNull('reference_app_id');
        }

        $updated = (clone $query)->update([
            'reference_app_id' => $app_type_id,
            'status_id' => $approved_status_id,
            'approved_by_id' => Auth::id(),
            'updated_at' => now(),
        ]);

        return [
            'data' => [
                'unit_id' => null,
                'year' => (int) $request->year,
                'plan_type' => $request->plan_type,
                'reference_app_id' => $app_type_id,
                'updated_count' => $updated,
            ],
            'message' => $request->plan_type === 'supplemental'
                ? 'Supplemental plan approved successfully!'
                : 'Annual APP approved successfully!',
            'info' => $request->plan_type === 'supplemental'
                ? "{$updated} new or changed PPMP " . ($updated === 1 ? 'entry was' : 'entries were') . " approved as an agency SPP update to the APP."
                : "{$updated} final PPMP " . ($updated === 1 ? 'entry was' : 'entries were') . " approved by Procurement Officer and tagged as {$app_type_name}.",
            'status' => true,
        ];
    }

    public function createPlan($request): array
    {
        return $this->create_plan($request);
    }

    public function create_spp_item_plan($request): array
    {
        $year = now()->year;
        $unit = ListUnit::query()->findOrFail((int) $request->unit_id);
        $app_type_id = ListDropdown::getID('Supplemental Procurement Plan', 'APP Type');
        $approved_status_id = ListStatus::getID('Approved', 'Procurement');
        $pending_status_id = ListStatus::getID('Pending', 'Procurement');
        $fund_cluster_id = ListDropdown::getID('Regular Fund', 'Fund Cluster')
            ?: ListDropdown::query()
                ->where(function ($query) {
                    $query->where('classification', 'Fund Cluster')
                        ->orWhere('type', 'Fund Cluster');
                })
                ->value('id');

        if (!$app_type_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Supplemental Procurement Plan is not configured in APP Type dropdowns.',
            ]);
        }

        if (!$approved_status_id || !$pending_status_id) {
            throw ValidationException::withMessages([
                'plan_type' => 'Approved or Pending procurement status is not configured.',
            ]);
        }

        $has_approved_app = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->where('status_id', $approved_status_id)
            ->whereHas('reference_app', function ($reference_query) {
                $reference_query->where('name', 'Annual Procurement Plan');
            })
            ->exists();

        if (!$has_approved_app) {
            throw ValidationException::withMessages([
                'plan_type' => 'APP must be approved for the current year before creating an SPP update.',
            ]);
        }

        $procurement = ProcurementPpmp::query()->create([
            'code' => 'SPP-' . $year . '-UNIT-' . str_pad((string) $unit->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) (ProcurementPpmp::query()->whereYear('date', $year)->where('unit_id', $unit->id)->count() + 1), 2, '0', STR_PAD_LEFT),
            'date' => $year . '-01-01',
            'purpose' => 'Supplemental Procurement Plan update for ' . $unit->name,
            'title' => 'Supplemental Procurement Plan',
            'division_id' => $unit->division_id,
            'unit_id' => $unit->id,
            'fund_cluster_id' => $fund_cluster_id,
            'reference_app_id' => $app_type_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'approved_by_id' => Auth::id(),
            'status_id' => $approved_status_id,
        ]);

        if (Schema::hasColumn('procurement_ppmps', 'request_id')) {
            $procurement->request_id = $this->create_ppmp_request()->id;
            $procurement->save();
        }

        $quantity = (float) $request->item_quantity;
        $unit_cost = (float) $request->item_unit_cost;
        $supporting_document = $this->store_supporting_document($request);

        ProcurementPpmpItem::query()->create([
            'item_no' => 1,
            'procurement_ppmp_id' => $procurement->id,
            'item_unit_type_id' => $request->item_unit_type_id,
            'item_name' => $request->item_name,
            'item_description' => $request->item_description,
            'project_type' => $request->project_type,
            'recommended_mode_of_procurement' => $request->recommended_mode_of_procurement,
            'end_of_procurement_activity' => $request->end_of_procurement_activity,
            'expected_delivery_date' => $request->expected_delivery_date,
            'attached_supporting_documents' => $request->attached_supporting_documents,
            'supporting_document_path' => $supporting_document['path'],
            'supporting_document_original_name' => $supporting_document['original_name'],
            'remarks' => $request->remarks,
            'item_quantity' => $quantity,
            'item_unit_cost' => $unit_cost,
            'total_cost' => $quantity * $unit_cost,
            'status_id' => $pending_status_id,
        ]);

        return [
            'data' => $this->show($procurement->id, (object) ['plan_type' => 'supplemental']),
            'message' => 'Supplemental plan item added successfully!',
            'info' => "{$request->item_name} was added to {$unit->name}'s SPP for {$year}.",
            'status' => true,
        ];
    }

    public function create_unit_ppmp($request): array
    {
        $year = (int) $request->year;
        $unit = ListUnit::query()->findOrFail((int) $request->unit_id);
        $pending_status_id = ListStatus::getID('Pending', 'Procurement');
        $fund_cluster_id = ListDropdown::getID('Regular Fund', 'Fund Cluster')
            ?: ListDropdown::query()
                ->where(function ($query) {
                    $query->where('classification', 'Fund Cluster')
                        ->orWhere('type', 'Fund Cluster');
                })
                ->value('id');

        if (!$pending_status_id) {
            throw ValidationException::withMessages([
                'unit_id' => 'Pending procurement status is not configured.',
            ]);
        }

        if (!$fund_cluster_id) {
            throw ValidationException::withMessages([
                'unit_id' => 'Fund Cluster dropdown is not configured.',
            ]);
        }

        $exists = ProcurementPpmp::query()
            ->where('unit_id', $unit->id)
            ->whereYear('date', $year)
            ->whereNull('reference_app_id')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'unit_id' => 'This unit already has a PPMP for the selected year.',
            ]);
        }

        $payload = [
            'code' => 'PPMP-' . $year . '-' . str_pad((string) $unit->id, 3, '0', STR_PAD_LEFT),
            'date' => $year . '-01-01',
            'purpose' => 'Project Procurement Management Plan for ' . $unit->name,
            'title' => 'Project Procurement Management Plan',
            'division_id' => $unit->division_id,
            'unit_id' => $unit->id,
            'fund_cluster_id' => $fund_cluster_id,
            'created_by_id' => Auth::id(),
            'requested_by_id' => Auth::id(),
            'approved_by_id' => Auth::id(),
            'status_id' => $pending_status_id,
        ];

        if (Schema::hasColumn('procurement_ppmps', 'request_id')) {
            $payload['request_id'] = $this->create_ppmp_request()->id;
        }

        $procurement = ProcurementPpmp::query()->create($payload);

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP created successfully!',
            'info' => "{$unit->name} now has an indicative PPMP for {$year}.",
            'status' => true,
        ];
    }

    public function createUnitPpmp($request): array
    {
        return $this->create_unit_ppmp($request);
    }

    public function submit_final($id, $request = null): array
    {
        $procurement = ProcurementPpmp::query()
            ->with($this->relations())
            ->findOrFail($id);

        if ($procurement->reference_app_id) {
            throw ValidationException::withMessages([
                'ppmp' => 'This PPMP is already included in an APP/SPP.',
            ]);
        }

        $reviewed_status_id = ListStatus::getID('Reviewed', 'Procurement');

        if (!$reviewed_status_id) {
            throw ValidationException::withMessages([
                'ppmp' => 'Reviewed procurement status is not configured.',
            ]);
        }

        $year = $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y');
        $is_annual_app_approval = data_get($request, 'plan_type') === 'annual';
        $updated = ProcurementPpmp::query()
            ->whereYear('date', $year)
            ->whereNull('reference_app_id')
            ->when(!$is_annual_app_approval, fn ($query) => $query->where('unit_id', $procurement->unit_id))
            ->update([
                'status_id' => $reviewed_status_id,
                'approved_by_id' => Auth::id(),
                'updated_at' => now(),
            ]);

        return [
            'data' => $this->show($id),
            'message' => $is_annual_app_approval ? 'APP marked as final.' : 'PPMP marked as final.',
            'info' => $is_annual_app_approval
                ? "{$updated} PPMP " . ($updated === 1 ? 'entry was' : 'entries were') . " marked final for APP-{$year}."
                : "{$updated} PPMP " . ($updated === 1 ? 'entry was' : 'entries were') . ' marked final by Procurement Officer and is ready for APP consolidation.',
            'status' => true,
        ];
    }

    public function submitFinal($id, $request = null): array
    {
        return $this->submit_final($id, $request);
    }

    public function add_item($id, $request): array
    {
        $procurement = ProcurementPpmp::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        if ($procurement->reference_app_id || in_array($procurement->status?->name, ['Reviewed', 'Approved'], true)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be added while the PPMP is still indicative.',
            ]);
        }

        $pending_status_id = ListStatus::getID('Pending', 'Procurement');
        $supporting_document = $this->store_supporting_document($request);
        $next_item_no = ((int) ProcurementPpmpItem::query()
            ->where('procurement_ppmp_id', $procurement->id)
            ->max('item_no')) + 1;
        $rows = collect($request->items ?: [[
            'item_name' => $request->item_name,
            'item_description' => $request->item_description,
            'item_quantity' => $request->item_quantity,
            'item_unit_type_id' => $request->item_unit_type_id,
            'item_unit_cost' => $request->item_unit_cost,
        ]]);

        $created_items = $rows->map(function ($row, $index) use ($procurement, $request, $pending_status_id, $supporting_document, $next_item_no) {
            $quantity = (float) data_get($row, 'item_quantity', 0);
            $unit_cost = (float) data_get($row, 'item_unit_cost', 0);

            $item = new ProcurementPpmpItem();
            $item->item_no = $next_item_no + $index;
            $item->procurement_ppmp_id = $procurement->id;
            $item->item_unit_type_id = data_get($row, 'item_unit_type_id');
            $item->item_name = data_get($row, 'item_name');
            $item->item_description = data_get($row, 'item_description');
            $item->project_type = $request->project_type;
            $item->recommended_mode_of_procurement = $request->recommended_mode_of_procurement;
            $item->end_of_procurement_activity = $request->end_of_procurement_activity;
            $item->expected_delivery_date = $request->expected_delivery_date;
            $item->attached_supporting_documents = $request->attached_supporting_documents;
            $item->supporting_document_path = $supporting_document['path'];
            $item->supporting_document_original_name = $supporting_document['original_name'];
            $item->remarks = $request->remarks;
            $item->item_quantity = $quantity;
            $item->item_unit_cost = $unit_cost;
            $item->total_cost = $quantity * $unit_cost;
            $item->status_id = $pending_status_id;
            $item->save();

            return $item;
        });

        return [
            'data' => $this->show($procurement->id),
            'message' => $created_items->count() === 1 ? 'PPMP item added successfully!' : 'PPMP items added successfully!',
            'info' => $created_items->count() . ' ' . ($created_items->count() === 1 ? 'item was' : 'items were') . " added to {$procurement->code}.",
            'status' => true,
        ];
    }

    public function addItem($id, $request): array
    {
        return $this->add_item($id, $request);
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
            'items.item_unit_type',
            'items.status',
            'status',
            'sub_status',
        ];
    }

    protected function store_supporting_document($request): array
    {
        if (!$request->hasFile('supporting_document_file')) {
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

    protected function ensureCanManage(): void
    {
        abort_unless(
            Auth::user()?->hasRole('Procurement Officer') || Auth::user()?->hasRole('Administrator'),
            403,
            'Only Procurement Officer or Administrator can manage PPMP APP plans.'
        );
    }

    protected function ensureCanMarkFinalPpmp(): void
    {
        abort_unless(
            Auth::user()?->hasRole('Procurement Officer'),
            403,
            'Only Procurement Officer can mark PPMP as final.'
        );
    }

    protected function ensureCanCreateSpp(): void
    {
        abort_unless(
            Auth::user()?->hasRole('Procurement Officer'),
            403,
            'Only Procurement Officer can create an SPP update.'
        );
    }

    protected function create_ppmp_request(): RequestModel
    {
        $type_id = ListData::getID('Procurement');

        if (!$type_id) {
            throw ValidationException::withMessages([
                'unit_id' => 'Procurement request type is not configured.',
            ]);
        }

        return RequestModel::query()->create([
            'code' => $this->generate_ppmp_request_code(),
            'type_id' => $type_id,
            'user_id' => Auth::id(),
            'is_completed' => 0,
            'is_sender_viewed' => 0,
            'is_receiver_viewed' => 0,
        ]);
    }

    protected function generate_ppmp_request_code(): string
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

        return 'REQUEST-' . now()->format('mY') . '-PPMP-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function registered_plan_years(string $plan_name): array
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

    protected function ppmp_query($request)
    {
        return ProcurementPpmp::query()
            ->with($this->relations())
            ->when($request->keyword, function ($query, $keyword) {
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
            })
            ->when($request->status, fn ($query, $status) => $query->where('status_id', $status))
            ->when($request->unit && !in_array($request->plan_type, ['annual', 'supplemental'], true), fn ($query, $unit) => $query->where('unit_id', $unit))
            ->when($request->plan_type && $request->plan_type !== 'all', function ($query) use ($request) {
                if ($request->plan_type === 'annual') {
                    $query->where(function ($annual_query) {
                        $annual_query->whereNull('reference_app_id')
                            ->orWhereHas('reference_app', function ($reference_query) {
                                $reference_query->where('name', 'Annual Procurement Plan');
                            });
                    });

                    return;
                }

                $query->whereHas('reference_app', function ($reference_query) {
                    $reference_query->where('name', 'Supplemental Procurement Plan');
                });
            })
            ->when($request->sort === 'oldest', function ($query) {
                $query->orderBy('date', 'ASC')->orderBy('created_at', 'ASC');
            })
            ->when($request->sort === 'pr_asc', fn ($query) => $query->orderBy('code', 'ASC'))
            ->when($request->sort === 'pr_desc', fn ($query) => $query->orderBy('code', 'DESC'))
            ->when(!in_array($request->sort, ['oldest', 'pr_asc', 'pr_desc'], true), function ($query) {
                $query->orderBy('date', 'DESC')->orderBy('created_at', 'DESC');
            });
    }

    protected function aggregate_source_query(ProcurementPpmp $procurement, ?string $plan_type = null)
    {
        $plan_name = $plan_type === 'annual'
            ? 'Annual Procurement Plan'
            : ($plan_type === 'supplemental' ? 'Supplemental Procurement Plan' : $procurement->reference_app?->name);
        $year = $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y');

        return ProcurementPpmp::query()
            ->with($this->relations())
            ->when($plan_name === 'Annual Procurement Plan', function ($query) use ($year) {
                $query->whereYear('date', $year)
                    ->where(function ($annual_query) {
                        $annual_query->whereHas('reference_app', function ($reference_query) {
                            $reference_query->where('name', 'Annual Procurement Plan');
                        })
                            ->orWhereHas('status', function ($status_query) {
                                $status_query->whereIn('name', ['Reviewed', 'Approved']);
                            });
                    });
            })
            ->when($plan_name === 'Supplemental Procurement Plan', function ($query) use ($year) {
                $query->whereYear('date', $year)
                    ->whereHas('reference_app', function ($reference_query) {
                        $reference_query->where('name', 'Supplemental Procurement Plan');
                    });
            })
            ->when(!$plan_name, function ($query) use ($procurement, $year) {
                $query->where('unit_id', $procurement->unit_id)
                    ->whereYear('date', $year)
                    ->whereNull('reference_app_id');
            });
    }

    protected function aggregate_by_unit(Collection $procurements, ?string $sort = null): Collection
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
                $year = $representative?->date ? date('Y', strtotime($representative->date)) : date('Y');
                $statuses = $unit_procurements->pluck('status.name')->filter()->unique()->values();
                $plan_name = $plan_names->isNotEmpty() ? $plan_names->implode(', ') : 'PPMP';
                $number_prefix = $plan_names->count() === 1 && $plan_names->first() === 'Supplemental Procurement Plan'
                    ? 'SPP'
                    : 'PPMP';

                $representative->setRelation('items', $items);
                $representative->setRelation('codes', $codes);
                $representative->setAttribute('ppmp_no_override', $number_prefix . '-' . $year . '-UNIT-' . str_pad((string) $representative->unit_id, 3, '0', STR_PAD_LEFT));
                $representative->setAttribute('pr_no_override', $pr_nos->implode(', '));
                $representative->setAttribute('plan_name_override', $plan_name);
                $representative->setAttribute('ppmp_status_override', $plan_names->isNotEmpty() || $statuses->contains('Reviewed') ? 'Final' : 'Indicative');
                $representative->setAttribute('aggregated_ppmp_count', $unit_procurements->count());
                $representative->setAttribute('classification_override', $classification_names->implode(', '));
                $representative->setAttribute('source_of_funds_override', $fund_sources->implode(', '));
                $representative->setAttribute('start_date_override', $unit_procurements->pluck('date')->filter()->sort()->first());
                $representative->setAttribute('approval_status_override', $this->approval_status_for_group($plan_names, $statuses));

                return $representative;
            })
            ->sort(fn ($first, $second) => $this->compare_ppmp_groups($first, $second, $sort))
            ->values();
    }

    protected function compare_ppmp_groups(ProcurementPpmp $first, ProcurementPpmp $second, ?string $sort = null): int
    {
        if ($sort === 'pr_asc') {
            return strcmp((string) $first->code, (string) $second->code);
        }

        if ($sort === 'pr_desc') {
            return strcmp((string) $second->code, (string) $first->code);
        }

        $first_date = strtotime((string) ($first->date ?: $first->created_at)) ?: 0;
        $second_date = strtotime((string) ($second->date ?: $second->created_at)) ?: 0;
        $first_created = strtotime((string) $first->created_at) ?: 0;
        $second_created = strtotime((string) $second->created_at) ?: 0;

        if ($sort === 'oldest') {
            return ($first_date <=> $second_date) ?: ($first_created <=> $second_created);
        }

        return ($second_date <=> $first_date) ?: ($second_created <=> $first_created);
    }

    protected function aggregate_agency_wide(Collection $procurements, string $plan_name = 'Annual Procurement Plan'): Collection
    {
        if ($procurements->isEmpty()) {
            return collect();
        }

        $representative = $procurements->first();
        $plan_short = $plan_name === 'Supplemental Procurement Plan' ? 'SPP' : 'APP';
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
        $year = $representative?->date ? date('Y', strtotime($representative->date)) : date('Y');
        $statuses = $procurements->pluck('status.name')->filter()->unique()->values();
        $plan_names = $procurements->pluck('reference_app.name')->filter()->unique()->values();
        $is_registered_plan = $plan_names->contains($plan_name);
        $is_final = $is_registered_plan || $statuses->contains('Reviewed') || $statuses->contains('Approved');

        $representative->setRelation('items', $items);
        $representative->setRelation('codes', $codes);
        $representative->setAttribute('ppmp_no_override', $plan_short . '-' . $year);
        $representative->setAttribute('pr_no_override', $pr_nos->implode(', '));
        $representative->setAttribute('plan_name_override', $plan_name);
        $representative->setAttribute('ppmp_status_override', $is_final ? 'Final' : 'Indicative');
        $representative->setAttribute('unit_override', [
            'id' => null,
            'name' => 'Agency-wide',
            'short' => $plan_short,
        ]);
        $representative->setAttribute('aggregated_ppmp_count', $procurements->count());
        $representative->setAttribute('classification_override', $classification_names->implode(', '));
        $representative->setAttribute('source_of_funds_override', $fund_sources->implode(', '));
        $representative->setAttribute('start_date_override', $procurements->pluck('date')->filter()->sort()->first());
        $representative->setAttribute('approval_status_override', $is_registered_plan ? ($plan_name === 'Supplemental Procurement Plan' ? 'Approved in SPP' : 'Approved in APP') : 'Draft');
        $representative->setAttribute('source_ppmps_override', $this->source_ppmp_summaries_by_unit($procurements, $plan_name));

        return collect([$representative]);
    }

    protected function aggregate_agency_wide_by_year(Collection $procurements, string $plan_name = 'Annual Procurement Plan'): Collection
    {
        return $procurements
            ->groupBy(fn ($procurement) => $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y', strtotime((string) $procurement->created_at)))
            ->sortKeysDesc()
            ->flatMap(fn (Collection $year_procurements) => $this->aggregate_agency_wide($year_procurements, $plan_name))
            ->values();
    }

    protected function source_ppmp_summaries_by_unit(Collection $procurements, string $plan_name = 'Annual Procurement Plan'): array
    {
        $approval_status = $plan_name === 'Supplemental Procurement Plan' ? 'Approved in SPP' : 'Approved in APP';
        $eligible_procurements = $procurements->filter(function ($procurement) use ($plan_name) {
            $reference_name = $procurement->reference_app?->name;
            $status_name = $procurement->status?->name;

            if ($reference_name === $plan_name) {
                return true;
            }

            return in_array($status_name, ['Reviewed', 'Approved'], true);
        });

        return $eligible_procurements
            ->groupBy('unit_id')
            ->map(function (Collection $unit_procurements) use ($approval_status) {
                $representative = $unit_procurements->first();
                $items = $unit_procurements->flatMap(fn ($procurement) => $procurement->items ?? collect())->values();
                $total_amount = $items->sum(fn ($item) => (float) ($item->total_cost ?? 0));
                $year = $representative?->date ? date('Y', strtotime($representative->date)) : date('Y');

                return [
                    'id' => $representative?->id,
                    'ppmp_no' => 'PPMP-' . $year . '-UNIT-' . str_pad((string) $representative?->unit_id, 3, '0', STR_PAD_LEFT),
                    'unit' => $representative?->unit,
                    'division' => $representative?->division,
                    'pr_no' => $unit_procurements->pluck('code')->filter()->unique()->implode(', '),
                    'items_count' => $items->count(),
                    'total_amount' => $total_amount,
                    'approval_status' => $approval_status,
                ];
            })
            ->values()
            ->all();
    }

    protected function plan_name_for_type(?string $plan_type): string
    {
        return $plan_type === 'supplemental'
            ? 'Supplemental Procurement Plan'
            : 'Annual Procurement Plan';
    }

    protected function approval_status_for_group(Collection $plan_names, Collection $statuses): string
    {
        if ($plan_names->isNotEmpty()) {
            if ($plan_names->contains('Annual Procurement Plan') && !$plan_names->contains('Supplemental Procurement Plan')) {
                return 'Approved in APP';
            }

            if ($plan_names->contains('Supplemental Procurement Plan') && !$plan_names->contains('Annual Procurement Plan')) {
                return 'Approved in SPP';
            }

            return 'Approved in APP and SPP';
        }

        if ($statuses->contains('Reviewed')) {
            return 'For Procurement Officer Approval';
        }

        if ($statuses->contains('Approved')) {
            return 'Approved';
        }

        return 'Draft';
    }
}
