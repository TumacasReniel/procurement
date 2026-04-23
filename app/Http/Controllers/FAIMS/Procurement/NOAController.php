<?php

namespace App\Http\Controllers\FAIMs\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\ProcurementBacNoaClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class NOAController extends Controller
{
     use HandlesTransaction;

    public $dropdown, $view, $noa , $print;

    public function __construct(
        ProcurementBacNoaClass $noa, 
        ViewClass $view, 
        PrintClass $print, 
        DropdownClass $dropdown
    ){
        $this->noa = $noa;
        $this->print = $print;
        $this->dropdown = $dropdown;
        $this->view = $view;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                  return $this->noa->lists($request);
            break;
            default:
                return inertia('Modules/FAIMS/Procurement/NOA/AllIndex', [
                    'dropdowns' => [
                        'statuses' => $this->dropdown->statuses('ProcurementBacNoa'),
                    ],
                ]);
            break;
        }
    }

    public function store(Request $request) {
        return back()->with([
            'message' => 'Manual NOA creation is not available here.',
            'info' => 'NOAs are generated from approved BAC resolutions.',
            'status' => 'warning',
        ]);
    }

 

    public function update($id, Request $request) {
        $result = $this->handleTransaction(function () use ($id, $request) {
            $option = $request->input('option') ?? $request->query('option');
            if (!$option) {
                if ($request->filled('comment')) {
                    $option = 'not_conformed';
                } elseif ($request->filled('status')) {
                    $option = 'update_status';
                } elseif ($request->has('body')) {
                    $option = 'update';
                } else {
                    $option = 'revert_status';
                }
            }

            switch($option){     
                case 'update':
                    return $this->noa->update($id, $request);
                break;
                case 'update_status':
                    return $this->noa->updateStatus($id, $request);
                break;
                case 'not_conformed':
                    return $this->noa->notConformed($id, $request);
                break;
                case 'revert_status':
                    return $this->noa->revertStatus($id, $request);
                break;
                default:
                    return [
                        'data' => null,
                        'message' => 'Invalid update option.',
                        'info' => 'No changes were applied.',
                        'status' => 'warning',
                    ];
                break;
            }   
           
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

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
