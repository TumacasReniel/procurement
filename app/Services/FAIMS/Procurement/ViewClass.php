<?php

namespace App\Services\FAIMS\Procurement;

use App\Services\DropdownClass;
use App\Models\Procurement;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementBac;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoNtp;
use Spatie\Activitylog\Models\Activity;

use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use App\Http\Resources\FAIMS\Procurement\ProcurementQuotationResource;
use Illuminate\Support\Facades\Auth;
use NumberFormatter;



class ViewClass
{
    public function __construct(DropdownClass $dropdown)
    {
        $this->dropdown = $dropdown;
    }

    public function procurements($request)
    {
        $data = ProcurementResource::collection(
            Procurement::with('status')
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('date', 'LIKE', "%{$keyword}%")
                        ->orWhere('created_at', 'LIKE', "%{$keyword}%")
                        ->orWhere('updated_at', 'LIKE', "%{$keyword}%");
                })
                ->when($request->status, function ($query, $status) {
                    $query->where('status_id', $status);
                })
                // Only restrict to own requests for Employees; Procurement Officer/Staff and Administrator see all
                ->when(
                    auth()->user()->hasRole('Employee') &&
                    !auth()->user()->hasRole('Procurement Officer') &&
                    !auth()->user()->hasRole('Procurement Staff'),
                    function ($query) {
                        $query->where('created_by_id', auth()->id());
                    }
                )
                ->orderBy('created_at', 'DESC')
                ->paginate($request->count)
        );
        return $data;
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

        // Division distribution
        $division_distribution = (clone $query)
            ->join('list_dropdowns as divisions', function ($join) {
                $join->on('procurements.division_id', '=', 'divisions.id')
                    ->where('divisions.classification', '=', 'Division');
            })
            ->select('divisions.name as division_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('divisions.name')
            ->get()
            ->map(function ($item) {
                return [
                    'division' => $item->division_name,
                    'count' => $item->count,
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
        ]);
    }


    public function show($id, $request)
    {

        $procurement = Procurement::with(
            'division',
            'unit',
            'codes',
            'items',
            'approved_by.profile',
            'items.item_unit_type',
            'items.status',
            'quotations.supplier',
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
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),
                        'supply_officers' => $this->dropdown->supply_officers(),
                        'suppliers' => $this->dropdown->suppliers(),
                        'delivery_places' => $this->dropdown->dropdowns('Place of Delivery'),
                        'roles' => $this->dropdown->roles(),
                    ],
                    'tab' => $request->tab,
                    'procurement' => $procurement,
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
                    'codes',
                    'items',
                    'approved_by.profile',
                    'items.item_unit_type',
                    'quotations.supplier',
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
                $procurement = Procurement::with('division', 'unit', 'codes', 'items', 'approved_by.profile', 'items.item_unit_type', 'quotations.supplier', 'quotations.items', 'status', 'sub_status', 'requested_by', 'created_by', 'comments.user.profile', 'comments.replies.user.profile')->findOrFail($id);
                return inertia('Modules/FAIMS/Procurement/View', [
                    'dropdowns' => [
                        'delivery_places' => $this->dropdown->dropdowns('Place of Delivery'),
                    ],
                    'tab' => 5,
                    'procurement' => $procurement,
                    'noa' => $noa,
                    'option' => $request->option,
                ]);
                break;



        }
    }



}
