<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\ProcurementPOClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class POController extends Controller
{
     use HandlesTransaction;

    public $dropdown, $view, $po , $print;

    public function __construct(
        ProcurementPOClass $po, 
        ViewClass $view, 
        PrintClass $print, 
        DropdownClass $dropdown
    ){
        $this->po = $po;
         $this->print = $print;
        $this->dropdown = $dropdown;
        $this->view = $view;
    }

    public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->po->lists($request);
            break;
            case 'purchase_order':
                  return $this->po->purchase_order($request);
            break;
            default:
                return inertia('Modules/FAIMS/Procurement/PurchaseOrder/List', [
                    'dropdowns' => [
                        'roles'  =>  \Auth::user()->roles,
                        'designation'  =>  \Auth::user()->designation,
                        'statuses' => $this->dropdown->statuses('Procurement'),
                    ],
                   
                ]); 
            break;

        }   
    }

     public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->po->save($request);
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
                    return $this->po->update($id , $request);
                break;
                case 'update_status':
                    return $this->po->updateStatus($id, $request);
                break;
                case 'not_conformed':
                    return $this->po->notConformed($id, $request);
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
