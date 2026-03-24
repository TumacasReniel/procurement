<?php

namespace App\Http\Controllers\FAIMS\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceRequiredDocument;
use App\Services\FAIMS\Finance\DocumentClass;
use App\Services\FAIMS\Finance\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class FinanceRequiredDocumentController extends Controller
{
      use HandlesTransaction;

    public $document, $view, $dropdown;

    public function __construct(
        DocumentClass $document, 
        ViewClass $view, 
        DropdownClass $dropdown
    ){
        $this->document = $document;
        $this->view = $view;
        $this->dropdown = $dropdown;
    }
    

     public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->document->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Finance/Documents/Index', [
                    'dropdowns' => [
                        'roles'  =>  \Auth::user()->roles,
                    ],
                ]); 
        }   
    }

    public function store(Request $request) {

        $result = $this->handleTransaction(function () use ($request) {
            return $this->document->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function update(Request $request, $id) {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->document->update( $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

  
}
