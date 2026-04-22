<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProcurementNoaPo;
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
            case 'iar_selection':
                  return $this->po->iarSelection($request);
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
            'status' => $result['status'] ?? 'success',
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
                } elseif ($request->filled('iar_id')) {
                    $option = 'update_iar_status';
                } elseif ($request->filled('ntp_id') || $request->has('ntp_body')) {
                    $option = 'update_ntp';
                } elseif ($request->has('selected_item_ids') || $request->has('delivered_items')) {
                    $option = 'update_iar_selection';
                } else {
                    $option = 'revert_status';
                }
            }

            switch($option){     
                case 'update':
                    return $this->po->update($id , $request);
                break;
                case 'update_status':
                    return $this->po->updateStatus($id, $request);
                break;
                case 'update_ntp':
                    return $this->po->updateNTP($id, $request);
                break;
                case 'update_iar_selection':
                    return $this->po->updateIARSelection($id, $request);
                break;
                case 'update_iar_status':
                    return $this->po->updateIARStatus($id, $request);
                break;
                case 'revert_iar_status':
                    return $this->po->revertIARStatus($id, $request);
                break;
                case 'not_conformed':
                    return $this->po->notConformed($id, $request);
                break;
                case 'revert_status':
                    return $this->po->revertStatus($id, $request);
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

        $isInertiaRequest = (bool) $request->header('X-Inertia');

        if (($request->expectsJson() || $request->ajax()) && !$isInertiaRequest) {
            return response()->json($result);
        }

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'] ?? 'success',
        ]);
        
    }

    public function show($id, Request $request){

        if($request->type){
            return $this->print->print($id, $request);
        }
        else if ($request->option === 'purchase_order') {
            $purchaseOrder = ProcurementNoaPo::findOrFail($id);
            $procurementId = $purchaseOrder->procurement_id;

            return redirect("/faims/procurements/{$procurementId}?option=view&tab=6&noa_id={$purchaseOrder->noa_id}");
        }
        else{
            return $this->view->show($id, $request);
        }
        
    }
}
