<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\SupplierClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;

class SupplierController extends Controller
{
     use HandlesTransaction;

    public $supplier, $view, $dropdown;

    public function __construct(
        SupplierClass $supplier, 
        ViewClass $view, 
        DropdownClass $dropdown
    ){
        $this->supplier = $supplier;
        $this->view = $view;
        $this->dropdown = $dropdown;
    }

    public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->supplier->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/Suppliers/Index', [
                    'dropdowns' => [
                        'roles'  =>  \Auth::user()->roles,
                        'attachment_types' => $this->dropdown->attachment_types(),
                    ],
                ]); 
        }   
    }

     public function store(Request $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->supplier->save($request);
        });


        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    
    public function update(Request $request, $id) {
        $request->merge(['id' => $id]);
        $result = $this->handleTransaction(function () use ($request) {
            return $this->supplier->update($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    public function status(Request $request, $id) {
        $result = $this->handleTransaction(function () use ($request, $id) {
            return $this->supplier->status($request, $id);
        });

        return response()->json([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function supply_officers(){
        $data = User::with('roles' , 'profile')
        ->whereHas('roles', function ($query) {
            $query->where('role_id', 12);
        })->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->profile->full_name ,
            ];
        });



        return $data;
    }
    


}
