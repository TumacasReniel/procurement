<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Services\DropdownClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\ProcurementCodeClass;
use  App\Http\Requests\Procurement\ProcurementCodeRequest;
use App\Models\ProcurementCode;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

            case 'budget_requests':
                $this->ensureCanReviewBudgetIncrease();

                return inertia('Modules/FAIMS/Procurement/Code/BudgetRequests');
            break;

            case 'budget_request_lists':
                $this->ensureCanReviewBudgetIncrease();
                $request->validate([
                    'keyword' => 'nullable|string|max:255',
                    'status' => 'nullable|in:all,pending,approved,rejected',
                    'count' => 'nullable|integer|min:1|max:100',
                    'page' => 'nullable|integer|min:1',
                ]);

                return $this->pap_codes->budgetIncreaseRequests($request);
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

    
    public function update(Request $request , $id) {
        if ($request->option === 'request_budget_increase') {
            return $this->request_budget_increase($request, $id);
        }

        if (in_array($request->option, ['approve_budget_increase', 'reject_budget_increase'], true)) {
            return $this->review_budget_increase($request, $id);
        }

        $this->ensureCanManage();
        $request->validate([
            'code' => 'required|string|max:255|unique:procurement_codes,code,' . $id,
            'title' => 'required|string|max:1000',
            'allocated_budget' => 'required|numeric|min:0',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 10),
            'app_type_id' => 'required|exists:list_dropdowns,id',
            'mode_of_procurement_id' => 'required|exists:list_dropdowns,id',
            'end_user_ids' => 'required|array|min:1',
            'end_user_ids.*' => 'exists:list_units,id',
        ], $this->procurement_code_messages());

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

    protected function request_budget_increase(Request $request, $id)
    {
        $this->ensureCanRequestBudgetIncrease();
        $this->validate_budget_increase_request($request, $id);

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

    protected function review_budget_increase(Request $request, $id)
    {
        $this->ensureCanReviewBudgetIncrease();
        $request->validate([
            'budget_log_id' => ['required', 'integer', 'exists:procurement_code_budget_logs,id'],
            'option' => ['required', 'in:approve_budget_increase,reject_budget_increase'],
        ]);

        $result = $this->handleTransaction(function () use ($request, $id) {
            return $request->option === 'approve_budget_increase'
                ? $this->pap_codes->approveBudgetIncrease($id, $request->budget_log_id)
                : $this->pap_codes->rejectBudgetIncrease($id, $request->budget_log_id);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    protected function validate_budget_increase_request(Request $request, $id): void
    {
        $request->validate([
            'option' => ['required', 'in:request_budget_increase'],
            'request_type' => ['required', Rule::in(['additional_budget', 'realignment'])],
            'source_procurement_code_id' => [
                Rule::requiredIf($request->input('request_type') === 'realignment'),
                'nullable',
                'integer',
                'exists:procurement_codes,id',
            ],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
            'attachment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], [
            'amount.required' => 'The additional budget amount is required.',
            'amount.numeric' => 'The additional budget amount must be a valid number.',
            'amount.min' => 'The additional budget amount must be greater than zero.',
            'request_type.required' => 'Please select the budget request type.',
            'request_type.in' => 'Please select a valid budget request type.',
            'source_procurement_code_id.required' => 'Please select the PAP code where the realigned budget will come from.',
            'source_procurement_code_id.exists' => 'The selected source PAP code is invalid.',
            'description.required' => 'Please provide a short justification for this budget request.',
            'description.max' => 'The justification may not be greater than 1000 characters.',
            'attachment.required' => 'Please upload the supporting document.',
            'attachment.file' => 'The supporting basis must be a valid file.',
            'attachment.mimes' => 'The supporting basis must be a PDF, Word, JPG, or PNG file.',
            'attachment.max' => 'The supporting basis must not exceed 5MB.',
        ]);

        if (
            $request->input('request_type') === 'realignment' &&
            (int) $request->input('source_procurement_code_id') === (int) $id
        ) {
            throw ValidationException::withMessages([
                'source_procurement_code_id' => 'The source PAP code must be different from the target PAP code.',
            ]);
        }

        if (
            $request->input('request_type') === 'realignment' &&
            $request->filled('source_procurement_code_id') &&
            $request->filled('amount')
        ) {
            $source_code = ProcurementCode::find($request->input('source_procurement_code_id'));
            $remaining_budget = (float) ($source_code?->remaining_budget ?? $source_code?->allocated_budget ?? 0);

            if ((float) $request->input('amount') > $remaining_budget) {
                throw ValidationException::withMessages([
                    'amount' => 'The realignment amount must not be greater than the source PAP code remaining balance.',
                ]);
            }
        }
    }

    protected function procurement_code_messages(): array
    {
        return [
            'code.required' => 'The PAP code is required.',
            'code.unique' => 'This PAP code already exists.',
            'title.required' => 'The project description/title is required.',
            'allocated_budget.required' => 'The allocated budget is required.',
            'allocated_budget.numeric' => 'The allocated budget must be a valid number.',
            'allocated_budget.min' => 'The allocated budget must be greater than 0.',
            'year.required' => 'The year is required.',
            'year.integer' => 'The year must be a valid year.',
            'app_type_id.required' => 'Please select an APP type.',
            'app_type_id.exists' => 'The selected APP type is invalid.',
            'mode_of_procurement_id.required' => 'Please select a mode of procurement.',
            'mode_of_procurement_id.exists' => 'The selected mode of procurement is invalid.',
            'end_user_ids.required' => 'Please select at least one end user.',
            'end_user_ids.min' => 'Please select at least one end user.',
            'end_user_ids.*.exists' => 'One or more selected end users are invalid.',
        ];
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
