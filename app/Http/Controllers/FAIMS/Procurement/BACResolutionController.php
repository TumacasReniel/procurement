<?php

namespace App\Http\Controllers\FAIMs\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\ProcurementBACClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class BACResolutionController extends Controller
{
     use HandlesTransaction;

     public $dropdown, $view, $bac_resolution , $user , $print;

    public function __construct(
        ProcurementBACClass $bac_resolution, 
        ViewClass $view,
        PrintClass $print, 
        DropdownClass $dropdown
    ){
        $this->bac_resolution = $bac_resolution;
        $this->print = $print;
        $this->dropdown = $dropdown;
        $this->view = $view;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                  return $this->bac_resolution->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/BACResolution/AllIndex', [
                    'dropdowns' => [
                        'statuses' => $this->dropdown->statuses('BAC Resolution'),
                    ],
                ]);
            break;
        }
    }

     public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->bac_resolution->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function update($id, Request $request) {
        $result = $this->handleTransaction(function () use ($id, $request) {
            switch($request->option){     
                case 'update':
                    return $this->bac_resolution->update($id , $request);
                break;
                case 'update_status':
                    return $this->bac_resolution->updateStatus($id, $request);
                break;
            }   
           
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function show($id, Request $request){

        if($request->type){
            return $this->print->print($id, $request);
        }
        else{
            return $this->view->show($id, $request);
        }
        
    }
}
