<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Procurement\SupplierRequest;
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
                        'statuses' => [
                            ['value' => 1, 'name' => 'Active'],
                            ['value' => 0, 'name' => 'Inactive'],
                        ],
                        'attachment_types' => $this->dropdown->attachment_types(),
                    ],
                ]); 
        }   
    }

     public function store(SupplierRequest $request) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->supplier->save($request);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $result['data'],
                'message' => $result['message'],
                'info' => $result['info'],
                'status' => $result['status'],
            ], 201);
        }

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    
    public function update(SupplierRequest $request, $id) {
        $request->merge(['id' => $id]);
        $result = $this->handleTransaction(function () use ($request) {
            return $this->supplier->update($request);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $result['data'],
                'message' => $result['message'],
                'info' => $result['info'],
                'status' => $result['status'],
            ]);
        }

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
            $query->where('list_roles.name', 'Supply Officer')
                ->where('user_roles.is_active', 1);
        })->orderBy('id')->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->profile?->full_name ?? ('User #' . $item->id),
            ];
        });

        return $data;
    }
    


}
