<?php

namespace App\Http\Controllers\FAIMS\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCreditor;
use App\Services\FAIMS\Finance\CreditorClass;
use App\Services\FAIMS\Finance\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\User;

class FinanceCreditorController extends Controller
{
      use HandlesTransaction;

    public $creditor, $view, $dropdown;

    public function __construct(
        CreditorClass $creditor, 
        ViewClass $view, 
        DropdownClass $dropdown
    ){
        $this->creditor = $creditor;
        $this->view = $view;
        $this->dropdown = $dropdown;
    }
    

     public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->creditor->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Finance/Creditors/Index', [
                    'dropdowns' => [
                        'supplier_options' => Supplier::select('id', 'name', 'code as account_code')->get()->toArray(),
                        'user_options' => User::select('id', 'username as name')->get()->toArray(),
                        'roles'  =>  \Auth::user()->roles,
                    ],
                ]); 
        }   
    }

    public function store(Request $request) {

        $result = $this->handleTransaction(function () use ($request) {
            return $this->creditor->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function show(FinanceCreditor $financeCreditor)
    {
        //
    }

    public function edit(FinanceCreditor $financeCreditor)
    {
        //
    }

    public function update(Request $request, FinanceCreditor $financeCreditor) {
        $request->merge(['id' => $financeCreditor->id]);
        $result = $this->handleTransaction(function () use ($request) {
            return $this->creditor->update( $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

  
    public function destroy(FinanceCreditor $financeCreditor)
    {
        $financeCreditor->delete();
        return back()->with([
            'message' => 'Creditor deleted successfully!',
            'info' => "You've successfully deleted Creditor.",
            'status' => 'success',
        ]);
    }
}
?>

