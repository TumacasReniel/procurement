<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Services\DropdownClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\ProcurementCodeClass;
use  App\Http\Requests\Procurement\ProcurementCodeRequest;

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
    
        switch($request->option){     
            case 'lists':
                return $this->pap_codes->lists($request);
            break;  

            case 'mode_of_procurements':
                return $this->dropdown->mode_of_procurements($request);
            break; 

            default:
                return inertia('Modules/FAIMS/Procurement/Code/Index', [
                'dropdowns' => [
                    'mode_of_procurements' => $this->dropdown->dropdowns('Mode of Procurement'),
                    'end_users' => $this->dropdown->list_units(),
                    'app_types' => $this->dropdown->dropdowns('APP Type'),
                ],
            ]); 
                  
        }   
    }

    public function store(ProcurementCodeRequest $request) {
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

}
