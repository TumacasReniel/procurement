<?php

namespace App\Http\Controllers\FAIMS\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceRequestType;
use App\Services\FAIMS\Finance\RequestTypeClass;
use App\Services\FAIMS\Finance\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class FinanceRequestTypeController extends Controller
{
    use HandlesTransaction;

    public $request_type, $view, $dropdown;

    public function __construct(
        RequestTypeClass $request_type, 
        ViewClass $view, 
        DropdownClass $dropdown
    ){
        $this->request_type = $request_type;
        $this->view = $view;
        $this->dropdown = $dropdown;
    }
    

    public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->request_type->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Finance/RequestTypes/Index', [
                    'dropdowns' => [
                        'documents' => $this->dropdown->documents(),
                    ],
                ]); 
        }   
    }

    public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->request_type->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function update(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->request_type->update($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function destroy($id)
    {
        $result = $this->handleTransaction(function () use ($id) {
            $type = FinanceRequestType::findOrFail($id);
            $type->delete();
            return [
                'data' => $id,
                'message' => 'Request type deleted successfully.',
                'info' => null,
                'status' => true,
            ];
        });

        return response()->json($result);
    }
}
