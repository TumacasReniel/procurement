<?php

namespace App\Http\Controllers\FAIMs\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\ResponsibilityCenterClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class BACResolutionController extends Controller
{
     use HandlesTransaction;

     public $dropdown, $view, $responsibility_center , $user ;

    public function __construct(
        ResponsibilityCenterClass $responsibility_center, 
        ViewClass $view,
        DropdownClass $dropdown
    ){
        $this->responsibility_center = $responsibility_center;
        $this->print = $print;
        $this->dropdown = $dropdown;
        $this->view = $view;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                  return $this->responsibility_center->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/ResponsibilityCenter/Index', [
                    'dropdowns' => [
                        'statuses' => $this->dropdown->statuses('BAC Resolution'),
                    ],
                ]);
            break;
        }
    }

     public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->responsibility_center->save($request);
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
             return $this->responsibility_center->update($id , $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function show($id, Request $request){
        return $this->view->show($id, $request);
        
    }
}
