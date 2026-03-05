<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\ProcurementQuotationClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class QuotationController extends Controller
{
     use HandlesTransaction;

    public $dropdown, $view, $quotation , $user , $print;

    public function __construct(
        ProcurementQuotationClass $quotation, 
        ViewClass $view, 
        PrintClass $print, 
        DropdownClass $dropdown
    ){
        $this->quotation = $quotation;
        $this->dropdown = $dropdown;
        $this->print = $print;
        $this->view = $view;
    }

    public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->view->quotations($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/Quotation/Index', [
                    'dropdowns' => [
                        'roles'  =>  \Auth::user()->roles,
                    ],
                ]); 
        }   
    }

     public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->quotation->save($request);
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

    public function destroy($id){
          $result = $this->handleTransaction(function () use ($id) {
            return $this->quotation->delete($id);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

}
