<?php

namespace App\Services\FAIMS\Procurement;

use App\Services\DropdownClass;
use App\Models\OrgSignatory;
use App\Models\Procurement;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementBac;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoNtp;
use App\Models\ProcurementAssignment;
use App\Models\ProcurementCode;
use App\Models\ResponsibilityCenter;
use App\Models\Supplier;
use App\Models\ListDropdown;
use Spatie\Activitylog\Models\Activity;

use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use App\Http\Resources\FAIMS\Procurement\ProcurementQuotationResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use NumberFormatter;



class ViewClass
{
    public function __construct(DropdownClass $dropdown)
    {
        $this->dropdown = $dropdown;
    }

    public function procurements($request)
    {
        $canSeeAllProcurements =
            auth()->user()->hasRole('Procurement Officer') ||
            auth()->user()->hasRole('Procurement Staff') ||
            auth()->user()->hasRole('Administrator');

        $procurementApprovalUserIds = $this->procurementApprovalUserIds();

        $data = ProcurementResource::collection(
            Procurement::with('status')
                ->withCount('comments')
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where(function ($searchQuery) use ($keyword) {
                        $searchQuery->where('code', 'LIKE', "%{$keyword}%")
                            ->orWhere('date', 'LIKE', "%{$keyword}%")
                            ->orWhere('created_at', 'LIKE', "%{$keyword}%")
                            ->orWhere('updated_at', 'LIKE', "%{$keyword}%");
                    });
                })
                ->when($request->report_type, function ($query, $reportType) {
                    $modeNames = $this->modeNamesForReportType($reportType);

                    if (empty($modeNames)) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereHas('codes.procurement_code.mode_of_procurement', function ($modeQuery) use ($modeNames) {
                            $modeQuery->whereIn('name', $modeNames);
                        });
                    }
                })
                ->when($request->mode, function ($query, $mode) {
                    $query->whereHas('codes.procurement_code', function ($codeQuery) use ($mode) {
                        $codeQuery->where('mode_of_procurement_id', $mode);
                    });
                })
                ->when($request->status, function ($query, $status) {
                    $query->where('status_id', $status);
                })
                // Employees only see their own PRs, while assigned signatories can also see PRs routed to them.
                ->when(!$canSeeAllProcurements, function ($query) use ($procurementApprovalUserIds) {
                    $query->where(function ($visibilityQuery) use ($procurementApprovalUserIds) {
                        $visibilityQuery->where('created_by_id', auth()->id());

                        if ($procurementApprovalUserIds->isNotEmpty()) {
                            $visibilityQuery->orWhereIn('approved_by_id', $procurementApprovalUserIds);
                        }
                    });
                })
                ->when($request->sort === 'oldest', function ($query) {
                    $query->orderBy('date', 'ASC')->orderBy('created_at', 'ASC');
                })
                ->when($request->sort === 'pr_asc', function ($query) {
                    $query->orderBy('code', 'ASC');
                })
                ->when($request->sort === 'pr_desc', function ($query) {
                    $query->orderBy('code', 'DESC');
                })
                ->when(!in_array($request->sort, ['oldest', 'pr_asc', 'pr_desc'], true), function ($query) {
                    $query->orderBy('date', 'DESC')->orderBy('created_at', 'DESC');
                })
                ->paginate($request->count)
        );
        return $data;
    }

    public function chatProcurements($request)
    {
        $canSeeAllProcurements =
            auth()->user()->hasRole('Procurement Officer') ||
            auth()->user()->hasRole('Procurement Staff') ||
            auth()->user()->hasRole('Administrator');

        $procurementApprovalUserIds = $this->procurementApprovalUserIds();

        $data = Procurement::query()
            ->select(['id', 'code', 'purpose', 'title', 'created_at'])
            ->withCount('comments')
            ->withMax('comments', 'created_at')
            ->with(['latest_comment.user.profile'])
            ->when(!$canSeeAllProcurements, function ($query) use ($procurementApprovalUserIds) {
                $query->where(function ($visibilityQuery) use ($procurementApprovalUserIds) {
                    $visibilityQuery->where('created_by_id', auth()->id());

                    if ($procurementApprovalUserIds->isNotEmpty()) {
                        $visibilityQuery->orWhereIn('approved_by_id', $procurementApprovalUserIds);
                    }
                });
            })
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($procurement) {
                return [
                    'id' => $procurement->id,
                    'code' => $procurement->code,
                    'purpose' => $procurement->purpose,
                    'title' => $procurement->title,
                    'created_at' => $procurement->created_at,
                    'comments_count' => $procurement->comments_count ?? 0,
                    'latest_comment_at' => $procurement->comments_max_created_at,
                    'latest_comment' => $procurement->latest_comment ? [
                        'id' => $procurement->latest_comment->id,
                        'content' => $procurement->latest_comment->content,
                        'created_at' => $procurement->latest_comment->created_at,
                        'created_ago' => $procurement->latest_comment->created_ago,
                        'user' => $procurement->latest_comment->user ? [
                            'id' => $procurement->latest_comment->user->id,
                            'username' => $procurement->latest_comment->user->username,
                            'name' => $procurement->latest_comment->user->profile?->full_name
                                ?? $procurement->latest_comment->user->username,
                        ] : null,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function commentThread($id)
    {
        $procurement = Procurement::with(
            'division',
            'status',
            'sub_status',
            'created_by.profile',
            'requested_by.profile',
            'approved_by.profile',
            'comments.user.profile',
            'comments.replies.user.profile'
        )
            ->withCount('comments')
            ->findOrFail($id);

        return response()->json([
            'data' => $procurement,
        ]);
    }

    protected function procurementApprovalUserIds(): Collection
    {
        return OrgSignatory::query()
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('oic_id', auth()->id());
            })
            ->where('is_active', 1)
            ->pluck('user_id')
            ->push(auth()->id())
            ->filter()
            ->unique()
            ->values();
    }

    private function modeNamesForReportType($reportType)
    {
        $map = [
            'goods_and_services' => [
                'Competitive Public Bidding',
                'Limited Source Bidding',
                'Direct Contracting',
                'Repeat Order',
                'Shopping',
                'Negotiated Procurement',
                'Small Value Procurement',
                'Lease of Venue and Community Facilities',
                'Agency-to-Agency',
            ],
            'infrastructure' => [
                'Competitive Public Bidding',
                'Limited Source Bidding',
                'Direct Contracting',
                'Negotiated Procurement',
                'Agency-to-Agency',
            ],
            'consulting' => [
                'Competitive Public Bidding',
                'Limited Source Bidding',
                'Negotiated Procurement',
            ],
        ];

        return $map[$reportType] ?? [];
    }



    public function quotations($request)
    {
        $data = ProcurementQuotationResource::collection(
            ProcurementQuotation::query()
                ->with('supplier.address', 'supply_officer')
                ->when($request->procurement_id, function ($query, $procurement_id) {
                    $query->where('procurement_id', $procurement_id);
                })
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('date', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('supplier', function ($query) use ($keyword) {
                            $query->where('name', 'LIKE', "%{$keyword}%");
                        })->orWhereHas('supply_officer', function ($query) use ($keyword) {
                            $query->where('name', 'LIKE', "%{$keyword}%");
                        })
                        ->orWhere('created_at', 'LIKE', "%{$keyword}%")
                        ->orWhere('updated_at', 'LIKE', "%{$keyword}%");
                })
                ->orderBy('created_at', 'DESC')
                ->paginate($request->count)
        );

        return $data;
    }

    public function dashboard($request)
    {
        $query = Procurement::query();

        // Apply period filters
        switch ($request->period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                break;
            case 'monthly':
                $year = $request->year ?? now()->year;
                $month = $request->month ?? now()->month;
                $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
                break;
            case 'quarterly':
                $year = $request->year ?? now()->year;
                $quarter = $request->quarter ?? 1;
                switch ($quarter) {
                    case 1:
                        $query->whereYear('created_at', $year)->whereMonth('created_at', '>=', 1)->whereMonth('created_at', '<=', 3);
                        break;
                    case 2:
                        $query->whereYear('created_at', $year)->whereMonth('created_at', '>=', 4)->whereMonth('created_at', '<=', 6);
                        break;
                    case 3:
                        $query->whereYear('created_at', $year)->whereMonth('created_at', '>=', 7)->whereMonth('created_at', '<=', 9);
                        break;
                    case 4:
                        $query->whereYear('created_at', $year)->whereMonth('created_at', '>=', 10)->whereMonth('created_at', '<=', 12);
                        break;
                }
                break;
            case 'yearly':
                $year = $request->year ?? now()->year;
                $query->whereYear('created_at', $year);
                break;
            case 'custom':
                if ($request->start_date && $request->end_date) {
                    $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
                }
                break;
            default:
                // 'all' - no filter
                break;
        }

        // Total procurements
        $total_procurements = $query->count();

        // Unit distribution (include units with zero procurements)
        $unit_counts = (clone $query)
            ->select('unit_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('unit_id');

        $unit_amounts = (clone $query)
            ->leftJoin('procurement_items', 'procurements.id', '=', 'procurement_items.procurement_id')
            ->select('procurements.unit_id')
            ->selectRaw('COALESCE(SUM(procurement_items.total_cost), 0) as distributed_amount')
            ->groupBy('procurements.unit_id');

        $division_distribution = \App\Models\ListUnit::query()
            ->leftJoinSub($unit_counts, 'unit_counts', function ($join) {
                $join->on('list_units.id', '=', 'unit_counts.unit_id');
            })
            ->leftJoinSub($unit_amounts, 'unit_amounts', function ($join) {
                $join->on('list_units.id', '=', 'unit_amounts.unit_id');
            })
            ->leftJoin('list_dropdowns as divisions', function ($join) {
                $join->on('list_units.division_id', '=', 'divisions.id')
                    ->where('divisions.classification', '=', 'Division');
            })
            ->selectRaw("list_units.name as unit_name")
            ->selectRaw("COALESCE(divisions.name, 'Unassigned') as division_name")
            ->selectRaw('COALESCE(unit_counts.count, 0) as count')
            ->selectRaw('COALESCE(unit_amounts.distributed_amount, 0) as distributed_amount')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'division' => $item->unit_name,
                    'division_name' => $item->division_name,
                    'count' => (int) $item->count,
                    'distributed_amount' => (float) $item->distributed_amount,
                ];
            });

        // Monthly trends
        $monthly_trends_query = Procurement::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month');

        // For quarters, limit to the quarter months
        if ($request->period === 'quarterly') {
            $year = $request->year ?? now()->year;
            $monthly_trends_query->whereYear('created_at', $year);
        } elseif ($request->period === 'monthly') {
            $year = $request->year ?? now()->year;
            $monthly_trends_query->whereYear('created_at', $year);
        } elseif ($request->period === 'yearly') {
            $year = $request->year ?? now()->year;
            $monthly_trends_query->whereYear('created_at', $year);
        } elseif ($request->period === 'custom' && $request->start_date && $request->end_date) {
            $monthly_trends_query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } else {
            // For other periods, show current year trends
            $monthly_trends_query->whereYear('created_at', now()->year);
        }

        $monthly_trends = $monthly_trends_query->get()
            ->map(function ($item) {
                return [
                    'month' => date('M', mktime(0, 0, 0, $item->month, 1)),
                    'count' => $item->count
                ];
            });

        // Recent procurements (always show latest 5, not filtered)
        $recent_procurements = Procurement::with('status', 'division')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        // Key metrics
        $for_reviews = (clone $query)->whereHas('status', function ($query) {
            $query->where('name', 'Pending');
        })->count();

        $for_approvals = (clone $query)->whereHas('status', function ($query) {
            $query->where('name', 'Reviewed');
        })->count();

        $completed_procurements = (clone $query)->whereHas('status', function ($query) {
            $query->where('name', 'Completed');
        })->count();

        $total_quotations = ProcurementQuotation::whereIn('procurement_id', $query->pluck('id'))->count();
        $total_bac_resolutions = ProcurementBac::whereIn('procurement_id', $query->pluck('id'))->count();
        $total_notice_of_awards = ProcurementBacNoa::whereIn('procurement_id', $query->pluck('id'))->count();
        $total_purchase_orders = ProcurementNoaPo::whereIn('procurement_id', $query->pluck('id'))->count();

        $total_assignments = ProcurementAssignment::count();
        $total_pap_codes = ProcurementCode::count();
        $total_remaining_pap_budget = (float) ProcurementCode::sum('remaining_budget');
        $total_allocated_pap_budget = (float) ProcurementCode::sum('allocated_budget');
        $total_responsibility_centers = ResponsibilityCenter::count();
        $total_modes_of_procurement = ListDropdown::query()
            ->whereIn('classification', ['mode_of_procurement', 'modes_of_procurement', 'Mode of Procurement'])
            ->count();
        $active_modes_of_procurement = ListDropdown::query()
            ->whereIn('classification', ['mode_of_procurement', 'modes_of_procurement', 'Mode of Procurement'])
            ->where('is_active', 1)
            ->count();
        $total_suppliers = Supplier::count();
        $active_suppliers = Supplier::query()
            ->where('approval_status', 'Approved')
            ->where('is_active', 1)
            ->count();
        $pending_supplier_approvals = Supplier::query()
            ->where('approval_status', 'Pending Approval')
            ->count();

        return response()->json([
            'total_procurements' => $total_procurements,
            'division_distribution' => $division_distribution,
            'monthly_trends' => $monthly_trends,
            'recent_procurements' => $recent_procurements,
            'for_reviews' => $for_reviews,
            'for_approvals' => $for_approvals,
            'completed_procurements' => $completed_procurements,
            'total_quotations' => $total_quotations,
            'total_bac_resolutions' => $total_bac_resolutions,
            'total_notice_of_awards' => $total_notice_of_awards,
            'total_purchase_orders' => $total_purchase_orders,
            'total_assignments' => $total_assignments,
            'total_pap_codes' => $total_pap_codes,
            'total_remaining_pap_budget' => $total_remaining_pap_budget,
            'total_allocated_pap_budget' => $total_allocated_pap_budget,
            'total_responsibility_centers' => $total_responsibility_centers,
            'total_modes_of_procurement' => $total_modes_of_procurement,
            'active_modes_of_procurement' => $active_modes_of_procurement,
            'total_suppliers' => $total_suppliers,
            'active_suppliers' => $active_suppliers,
            'pending_supplier_approvals' => $pending_supplier_approvals,
        ]);
    }


    public function show($id, $request)
    {
        $selectedNoa = $request->noa_id
            ? ProcurementBacNoa::with('purchase_order', 'procurement_quotation.supplier.address', 'items')
                ->find($request->noa_id)
            : null;

        $procurement = Procurement::with(
            'division',
            'unit',
            'classification',
            'codes',
            'items',
            'approved_by.profile',
            'items.item_unit_type',
            'items.status',
            'quotations.supplier',
            'quotations.supplier.address',
            'quotations.status',
            'quotations.items',
            'status',
            'sub_status',
            'requested_by',
            'created_by'
            ,
            'bac_resolutions.comments.user.profile',
            'bac_resolutions.comments.replies.user.profile',
            'noas.status',
            'noas.items.item.status',
            'noas.comments.user.profile',
            'noas.comments.replies.user.profile',
            'pos.status',
            'pos.iars.status',
            'pos.noa.items.item.item.item_unit_type',
            'pos.comments.user.profile',
            'pos.comments.replies.user.profile',
            'comments.user.profile',
            'comments.replies.user.profile'
        )->findOrFail($id);

        $assignees = [];
        $globalAssignments = ProcurementAssignment::with('user.profile')->get();
        foreach ($globalAssignments as $assignment) {
            $name = $assignment->user?->profile?->full_name ?? $assignment->user?->name ?? 'User #' . $assignment->user_id;
            if (!$name) {
                continue;
            }
            $assignees[$assignment->status][] = $name;
        }
        foreach ($assignees as $status => $names) {
            $assignees[$status] = array_values(array_unique($names));
        }
        $procurement->assignees = $assignees;

        // Add status distribution for the status flow panel
        $procurement->status_distribution = [
            'pending' => 0, // Assuming pending is not counted here, or calculate based on logic
            'for_review' => 0,
            'for_approval' => 0,
            'approved' => 0,
            'rfq' => $procurement->quotations ? $procurement->quotations->count() : 0,
            'bidding' => ($procurement->bids ? $procurement->bids->count() : 0) + ($procurement->quotations ? $procurement->quotations->count() : 0),
            'bac_resolution' => $procurement->bac_resolutions ? $procurement->bac_resolutions->count() : 0,
            'noa' => $procurement->noas ? $procurement->noas->count() : 0,
            'po' => $procurement->pos ? $procurement->pos->count() : 0,
        ];

        $logs = Activity::where(function ($query) use ($id) {
            $query->where('subject_type', Procurement::class)
                ->where('subject_id', $id);
        })->orWhere(function ($query) use ($id) {
            $query->where('subject_type', ProcurementQuotation::class)
                ->whereIn('subject_id', ProcurementQuotation::where('procurement_id', $id)->pluck('id'));
        })->orWhere(function ($query) use ($id) {
            $query->where('subject_type', ProcurementBac::class)
                ->whereIn('subject_id', ProcurementBac::where('procurement_id', $id)->pluck('id'));
        })->orWhere(function ($query) use ($id) {
            $query->where('subject_type', ProcurementBacNoa::class)
                ->whereIn('subject_id', ProcurementBacNoa::where('procurement_id', $id)->pluck('id'));
        })->orWhere(function ($query) use ($id) {
            $query->where('subject_type', ProcurementNoaPo::class)
                ->whereIn('subject_id', ProcurementNoaPo::where('procurement_id', $id)->pluck('id'));
        })->with('causer.profile')->orderBy('created_at', 'desc')->get();
        switch ($request->option) {

            case 'view':
                return inertia('Modules/FAIMS/Procurement/View', [
                    'dropdowns' => [
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'classifications' => $this->dropdown->dropdowns('Classification'),
                        'reference_apps' => $this->dropdown->dropdowns('Reference APP'),
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'statuses' => $this->dropdown->statuses('Procurement'),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),
                        'supply_officers' => $this->dropdown->supply_officers(),
                        'suppliers' => $this->dropdown->suppliers(),
                        'delivery_places' => $this->dropdown->dropdowns('Place of Delivery'),
                        'roles' => $this->dropdown->roles(),
                    ],
                    'tab' => $request->tab,
                    'procurement' => $procurement,
                    'noa' => $selectedNoa,
                    'create_po' => $request->create_po,
                    'logs' => $logs,
                    'auth' => auth()->user()->load('profile'),
                    'option' => $request->option,
                ]);
                break;

            case 'view_notice_of_awards':
                $bac_resolution = ProcurementBac::findOrFail($request->bac_reso_id);
                return inertia('Modules/FAIMS/Procurement/View', [
                    'dropdowns' => [
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'statuses' => $this->dropdown->statuses('Procurement'),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),
                        'supply_officers' => $this->dropdown->supply_officers(),
                        'suppliers' => $this->dropdown->suppliers(),
                        'delivery_places' => $this->dropdown->dropdowns('Place of Delivery'),
                        'roles' => $this->dropdown->roles(),
                    ],
                    'procurement' => $procurement,
                    'bac_resolution' => $bac_resolution,
                    'option' => $request->option,
                ]);
                break;
            case 'edit':
            case 'review':
            case 'approve':
                $procurement = Procurement::with(
                    'division',
                    'unit',
                    'classification',
                    'reference_app',
                    'codes',
                    'items',
                    'approved_by.profile',
                    'items.item_unit_type',
                    'quotations.supplier',
                    'quotations.supplier.address',
                    'quotations.status',
                    'quotations.items',
                    'status',
                    'sub_status',
                    'requested_by',
                    'created_by'
                    ,
                    'bac_resolutions.comments.user.profile',
                    'bac_resolutions.comments.replies.user.profile',
                    'noas.comments.user.profile',
                    'noas.comments.replies.user.profile',
                    'pos.comments.user.profile',
                    'pos.comments.replies.user.profile',
                    'comments.user.profile',
                    'comments.replies.user.profile'
                )->findOrFail($id);
                return inertia('Modules/FAIMS/Procurement/CreatePage', [
                    'dropdowns' => [
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'classifications' => $this->dropdown->dropdowns('Classification'),
                        'reference_apps' => $this->dropdown->dropdowns('Reference APP'),
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),

                    ],
                    'procurement' => $procurement,
                    'option' => $request->option,
                ]);
                break;

            case 'quotations':
                return inertia('Modules/FAIMS/Procurement/Quotations/Index', [
                    'dropdowns' => [
                        'supply_officers' => $this->dropdown->supply_officers(),
                        'suppliers' => $this->dropdown->suppliers(),
                    ],
                    'procurement' => $procurement,
                    'option' => $request->option,
                ]);
                break;

            case 'bids':
                return inertia('Modules/FAIMS/Procurement/Bids/Index', [
                    'dropdowns' => [
                        'suppliers' => $this->dropdown->suppliers(),
                    ],
                    'procurement' => $procurement,
                    'option' => $request->option,
                ]);
                break;

            case 'bac_resolutions':
                $logs = Activity::where(function ($query) use ($id) {
                    $query->where('subject_type', ProcurementBac::class)
                        ->whereIn('subject_id', ProcurementBac::where('procurement_id', $id)->pluck('id'));
                })->with('causer')->orderBy('created_at', 'desc')->get();

                return inertia('Modules/FAIMS/Procurement/BACResolution/Index', [
                    'procurement' => $procurement,
                    'logs' => $logs,
                    'option' => $request->option,
                ]);
                break;

            case 'bac_resolution_logs':
                $logs = Activity::where(function ($query) use ($id, $request) {
                    $query->where('subject_type', ProcurementBac::class);
                    if ($request->bac_resolution_id) {
                        $query->where('subject_id', $request->bac_resolution_id);
                    } else {
                        $query->whereIn('subject_id', ProcurementBac::where('procurement_id', $id)->pluck('id'));
                    }
                })->with('causer.profile')->orderBy('created_at', 'desc')->get();

                return response()->json(['logs' => $logs]);
                break;

            case 'notice_of_awards':
                $bac_resolution = ProcurementBac::findOrFail($request->bac_reso_id);
                return inertia('Modules/FAIMS/Procurement/NOA/Index', [
                    'procurement' => $procurement,
                    'bac_resolution' => $bac_resolution,
                    'option' => $request->option,
                ]);
                break;

            case 'purchase_order':
                $noa = ProcurementBacNoa::with('purchase_order', 'procurement_quotation.supplier.address', 'items', )->findOrFail($request->noa_id);
                $procurement = Procurement::with('division', 'unit', 'codes', 'items', 'approved_by.profile', 'items.item_unit_type', 'quotations.supplier', 'quotations.supplier.address', 'quotations.status', 'quotations.items', 'status', 'sub_status', 'requested_by', 'created_by', 'comments.user.profile', 'comments.replies.user.profile')->findOrFail($id);
                return inertia('Modules/FAIMS/Procurement/View', [
                    'dropdowns' => [
                        'delivery_places' => $this->dropdown->dropdowns('Place of Delivery'),
                        'statuses' => $this->dropdown->statuses('Procurement'),
                    ],
                    'tab' => 6,
                    'procurement' => $procurement,
                    'noa' => $noa,
                    'option' => $request->option,
                ]);
                break;



        }
    }



}
