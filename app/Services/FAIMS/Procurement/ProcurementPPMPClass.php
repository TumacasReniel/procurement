<?php

namespace App\Services\FAIMS\Procurement;

use App\Http\Resources\FAIMS\Procurement\ProcurementPPMPResource;
use App\Models\ListData;
use App\Models\ListDropdown;
use App\Models\ListStatus;
use App\Models\ListUnit;
use App\Models\Procurement;
use App\Models\ProcurementItem;
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
                'app_types' => $this->dropdown->dropdowns('APP Type'),
            ],
        ];
    }

    public function show_page_props($id, $request = null): array
    {
        return [
            'ppmp' => $this->show($id, $request),
            'dropdowns' => [
                'unit_types' => $this->dropdown->unit_types(),
            ],
        ];
    }

    public function available_ppmp_units($request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $used_unit_ids = Procurement::query()
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

    public function show($id, $request = null): array
    {
        $procurement = Procurement::query()
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

        $query = Procurement::query()
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
            $has_approved_app = Procurement::query()
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

        $exists = Procurement::query()
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
            'code' => 'PPMP-' . $year . '-UNIT-' . str_pad((string) $unit->id, 3, '0', STR_PAD_LEFT),
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

        if (Schema::hasColumn('procurements', 'request_id')) {
            $payload['request_id'] = $this->create_ppmp_request()->id;
        }

        $procurement = Procurement::query()->create($payload);

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP created successfully!',
            'info' => "{$unit->name} now has an indicative PPMP for {$year}.",
            'status' => true,
        ];
    }

    public function submit_final($id, $request = null): array
    {
        $procurement = Procurement::query()
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
        $updated = Procurement::query()
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

    public function add_item($id, $request): array
    {
        $procurement = Procurement::query()
            ->with(['reference_app', 'status'])
            ->findOrFail($id);

        if ($procurement->reference_app_id || in_array($procurement->status?->name, ['Reviewed', 'Approved'], true)) {
            throw ValidationException::withMessages([
                'item' => 'Items can only be added while the PPMP is still indicative.',
            ]);
        }

        $pending_status_id = ListStatus::getID('Pending', 'Procurement');
        $quantity = (float) $request->item_quantity;
        $unit_cost = (float) $request->item_unit_cost;
        $next_item_no = ((int) ProcurementItem::query()
            ->where('procurement_id', $procurement->id)
            ->max('item_no')) + 1;

        $item = new ProcurementItem();
        $item->item_no = $next_item_no;
        $item->procurement_id = $procurement->id;
        $item->item_unit_type_id = $request->item_unit_type_id;
        $item->item_name = $request->item_name;
        $item->item_description = $request->item_description;
        $item->item_quantity = $quantity;
        $item->item_unit_cost = $unit_cost;
        $item->total_cost = $quantity * $unit_cost;
        $item->status_id = $pending_status_id;
        $item->save();

        return [
            'data' => $this->show($procurement->id),
            'message' => 'PPMP item added successfully!',
            'info' => "{$item->item_name} was added to {$procurement->code}.",
            'status' => true,
        ];
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

    protected function ppmp_query($request)
    {
        return Procurement::query()
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

    protected function aggregate_source_query(Procurement $procurement, ?string $plan_type = null)
    {
        $plan_name = $plan_type === 'annual'
            ? 'Annual Procurement Plan'
            : ($plan_type === 'supplemental' ? 'Supplemental Procurement Plan' : $procurement->reference_app?->name);
        $year = $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y');

        return Procurement::query()
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

    protected function compare_ppmp_groups(Procurement $first, Procurement $second, ?string $sort = null): int
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
