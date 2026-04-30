<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Services\DropdownClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\ProcurementCodeClass;
use  App\Http\Requests\Procurement\ProcurementCodeRequest;
use App\Http\Requests\Procurement\ProcurementCodeBudgetIncreaseRequest;
use App\Http\Requests\Procurement\ProcurementCodeBudgetRequestListRequest;

class ProcurementCodeController extends Controller
{
     use HandlesTransaction;
    public $dropdown, $view;

    public function __construct(
        ProcurementCodeClass $pap_codes, 
        DropdownClass $dropdown,
    ){
        $this->pap_codes = $pap_codes;
        $this->dropdown = $dropdown;
    }

    public function index(Request $request){
        $this->ensureCanView();
    
        switch($request->option){     
            case 'lists':
                return $this->pap_codes->lists($request);
            break;  

            case 'mode_of_procurements':
                return $this->dropdown->mode_of_procurements($request);
            break; 

            case 'source_budget_codes':
                return $this->pap_codes->sourceBudgetCodes($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/Code/Index', [
                'dropdowns' => [
                    'app_types' => $this->dropdown->dropdowns('APP Type'),
                    'mode_of_procurements' => $this->dropdown->dropdowns('mode_of_procurement'),
                    'end_users' => $this->dropdown->list_units(),
                ],
            ]); 
                  
        }   
    }

    public function budgetRequests(ProcurementCodeBudgetRequestListRequest $request)
    {
        $this->ensureCanReviewBudgetIncrease();

        if ($request->option === 'lists') {
            return $this->pap_codes->budgetIncreaseRequests($request);
        }

        return inertia('Modules/FAIMS/Procurement/Code/BudgetRequests');
    }

    public function store(ProcurementCodeRequest $request) {
        $this->ensureCanManage();

        $result = $this->handleTransaction(function () use ($request) {
            return $this->pap_codes->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    
    public function update(ProcurementCodeRequest $request , $id) {
        $this->ensureCanManage();

        $result = $this->handleTransaction(function () use ($request ,$id) {
            return $this->pap_codes->update($request, $id);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    public function show($id)
    {
        $this->ensureCanView();

        $profile = $this->pap_codes->profile($id);

        return inertia('Modules/FAIMS/Procurement/Code/Profile', [
            'papCode' => $profile['data'],
            'logs' => $profile['logs'],
        ]);
    }

    public function requestBudgetIncrease(ProcurementCodeBudgetIncreaseRequest $request, $id)
    {
        $this->ensureCanRequestBudgetIncrease();

        $result = $this->handleTransaction(function () use ($request, $id) {
            return $this->pap_codes->requestBudgetIncrease($id, $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function approveBudgetIncrease($id, $budgetLog)
    {
        $this->ensureCanReviewBudgetIncrease();

        $result = $this->handleTransaction(function () use ($id, $budgetLog) {
            return $this->pap_codes->approveBudgetIncrease($id, $budgetLog);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function rejectBudgetIncrease($id, $budgetLog)
    {
        $this->ensureCanReviewBudgetIncrease();

        $result = $this->handleTransaction(function () use ($id, $budgetLog) {
            return $this->pap_codes->rejectBudgetIncrease($id, $budgetLog);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    protected function ensureCanManage(): void
    {
        abort_unless(
            $this->pap_codes->canManageProcurementCodes(auth()->user()),
            403,
            'Only Procurement Officer or Administrator can manage PAP codes.'
        );
    }

    protected function ensureCanView(): void
    {
        abort_unless(
            $this->pap_codes->canViewProcurementCodes(auth()->user()),
            403,
            'You do not have permission to access PAP codes.'
        );
    }

    protected function ensureCanReviewBudgetIncrease(): void
    {
        abort_unless(
            $this->pap_codes->canReviewBudgetIncrease(auth()->user()),
            403,
            'Only Budget Officer can review PAP code budget increase requests.'
        );
    }

    protected function ensureCanRequestBudgetIncrease(): void
    {
        abort_unless(
            $this->pap_codes->canRequestBudgetIncrease(auth()->user()),
            403,
            'Only Procurement Officer can request PAP code budget increases.'
        );
    }
}
