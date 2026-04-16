<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Services\DropdownClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\ProcurementClass;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\Executive\Users\SaveClass;
use App\Events\CommentAdded;
use App\Models\OrgChart;
use App\Models\OrgSignatory;
use App\Models\User;
use App\Models\ListDropdown;

class ProcurementController extends Controller
{
     use HandlesTransaction;

    public $dropdown, $view, $procurement , $user , $print;

    public function __construct(
        DropdownClass $dropdown,
        ViewClass $view, 
        PrintClass $print, 
        ProcurementClass $procurement,
        SaveClass $user,
    ){
        $this->dropdown = $dropdown;
        $this->view = $view;
        $this->print = $print;
        $this->procurement = $procurement;
        $this->user = $user;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                return $this->view->procurements($request);
            break;

            case 'quotations':
                return $this->view->quotations($request);
            break;

            case 'dashboard':
                return $this->view->dashboard($request);
            break;

            default:
                $regionalDirector = $this->dropdown->regional_director();
                $procurementApprovalUserIds = OrgSignatory::query()
                    ->where(function ($query) {
                        $query->where('user_id', \Auth::id())
                            ->orWhere('oic_id', \Auth::id());
                    })
                    ->where('is_active', 1)
                    ->pluck('user_id')
                    ->push(\Auth::id())
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return inertia('Modules/FAIMS/Procurement/Index', [
                    'dropdowns' => [
                        'roles'  =>  \Auth::user()->roles,
                        'designation'  =>  \Auth::user()->org_chart?->designation,
                        'statuses' => $this->dropdown->statuses('Procurement'),
                        'types' => $this->dropdown->dropdowns('Type'),
                        'modes' => $this->dropdown->dropdowns('Mode'),
                    ],
                    'regional_director'  =>  $regionalDirector,
                    'is_regional_director' => $regionalDirector && $regionalDirector['value'] == \Auth::id(),
                    'procurement_approval_user_ids' => $procurementApprovalUserIds,
                ]);
        }
    }

    public function create_index(Request $request){
        
        switch($request->option){     
            case 'units':
               return  $this->dropdown->units($request->code);
            break;
            case 'unit_type':
                return $this->dropdown->unit_type($request->code);
            break;
            case 'title':
                return $this->procurement->procurement_title($request->id);
            break;
            default:
                $division_head = null;
                if (\Auth::user()->organization && \Auth::user()->organization->division_id) {
                    $division_head = $this->dropdown->division_head(\Auth::user()->organization->division_id);
                }

                return inertia('Modules/FAIMS/Procurement/CreatePage', [
                    'dropdowns' => [
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'classifications' => $this->dropdown->dropdowns('Classification'),
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),
                        'regional_director' => $this->dropdown->regional_director(),
                        'division_head' => $division_head,
                    ],
                    'option' => $request->option,
                ]);
            break;
        } 
    }

    public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->procurement->save($request);
        });

        return redirect()->route('procurement.index')->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }



     public function update($id, Request $request) {
        $result = $this->handleTransaction(function () use ($id, $request) {
            switch($request->option){     
                case 'edit':
                    return $this->procurement->update($id, $request);
                break;
                case 'review':
                    return $this->procurement->review($id, $request);
                break;
                case 'approve':
                    return $this->procurement->approve($id, $request);
                break;
                case 'cancel':
                    return $this->procurement->cancel($id, $request);
                break;
            }   
           
        });

        return redirect()->route('procurement.index')->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    public function dashboard(Request $request){
        return inertia('Modules/FAIMS/Procurement/Dashboard', [
            'dropdowns' => [
                'roles'  =>  \Auth::user()->roles,
                'designation'  =>  \Auth::user()->designation,
            ],
        ]);
    }

    public function reports(Request $request){
        if ($request->option === 'print') {
            return $this->print->printReport($request);
        }

        $signatories = $this->reportSignatories();

        return inertia('Modules/FAIMS/Procurement/Reports/Index', [
            'dropdowns' => [
                'roles'  =>  \Auth::user()->roles,
                'designation'  =>  \Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'types' => $this->dropdown->dropdowns('Type'),
                'modes' => $this->dropdown->dropdowns('Mode of Procurement'),
            ],
            'signatories' => $signatories,
        ]);
    }

    private function reportSignatories(): array
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

        $assistantRegionalDirector = OrgChart::with('user.profile', 'designation')
            ->where('designation_id', ListDropdown::getID('Assistant Regional Director', 'Designation'))
            ->first();

        return [
            'prepared_by' => array_slice($procurementStaff, 0, 2),
            'supply_officer' => $supplyOfficer ? [
                'name' => strtoupper($supplyOfficer->profile?->full_name ?? ('USER #' . $supplyOfficer->id)),
                'role' => 'Supply Officer',
            ] : null,
            'noted_by' => $assistantRegionalDirector ? [
                'name' => strtoupper($assistantRegionalDirector->user?->profile?->full_name ?? ''),
                'designation' => 'ARD-FASS',
            ] : null,
        ];
    }

    public function show($id, Request $request){
        if($request->type){
            return $this->print->print($id, $request);
        }
        else{
            return $this->view->show($id, $request);
        }

    }

    public function addComment($id, Request $request) {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $result = $this->handleTransaction(function () use ($id, $request) {
            $procurement = \App\Models\Procurement::findOrFail($id);

            $comment = $procurement->comments()->create([
                'content' => $request->content,
                'user_id' => auth()->id(),
            ]);

            // Broadcast the comment to other users
            broadcast(new CommentAdded($comment))->toOthers();

            return [
                'data' => $comment->load('user.profile'),
                'message' => 'Comment added successfully',
                'info' => 'Your comment has been added to the procurement.',
                'status' => true,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $result['data'],
                'status' => $result['status'],
                'message' => $result['message'],
                'info' => $result['info'],
            ]);
        }

        return back()->with([
            'data' => $result['data'],
            'status' => $result['status'],
        ]);


    }


}
