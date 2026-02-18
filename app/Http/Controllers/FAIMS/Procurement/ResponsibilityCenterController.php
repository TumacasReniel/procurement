<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\ResponsibilityCenterClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class ResponsibilityCenterController extends Controller
{
     use HandlesTransaction;

     public $dropdown, $view, $responsibility_center , $user ;

    public function __construct(
        ResponsibilityCenterClass $responsibility_center, 
        ViewClass $view,
        DropdownClass $dropdown
    ){
        $this->responsibility_center = $responsibility_center;
        $this->dropdown = $dropdown;
        $this->view = $view;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                  return $this->responsibility_center->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/ResponsibilityCenters/Index', [
                    'dropdowns' => [
                        'units' => $this->dropdown->list_units(),
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

    public function update(Request $request) {
   
        $result = $this->handleTransaction(function () use ($request) {
             return $this->responsibility_center->update($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function show($id, Request $request){
        return $this->responsibility_center->show($id);
    }

    public function destroy($id, Request $request){
        $result = $this->handleTransaction(function () use ($id) {
            return $this->responsibility_center->destroy($id);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
