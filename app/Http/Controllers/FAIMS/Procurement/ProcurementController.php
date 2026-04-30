<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\FAIMS\Procurement\ProcurementClass;
use App\Services\FAIMS\Procurement\ViewClass;

class ProcurementController extends Controller
{
     use HandlesTransaction;

    public $view, $procurement, $print;

    public function __construct(
        ViewClass $view, 
        PrintClass $print, 
        ProcurementClass $procurement,
    ){
        $this->view = $view;
        $this->print = $print;
        $this->procurement = $procurement;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                return $this->view->procurements($request);
            break;

            case 'chat_lists':
                return $this->view->chatProcurements($request);
            break;

            case 'quotations':
                return $this->view->quotations($request);
            break;

            case 'dashboard':
                return $this->view->dashboard($request);
            break;

            default:
                return inertia('Modules/FAIMS/Procurement/Index', $this->procurement->indexPageProps($request));
        }
    }

    public function create(Request $request){
        $data = $this->procurement->createIndexData($request);

        if (!is_null($data)) {
            return $data;
        }

        return inertia('Modules/FAIMS/Procurement/CreatePage', $this->procurement->createPageProps($request));
    }

    public function store(Request $request) {
        $this->procurement->validateProcurementBudgetAvailability($request);

        $result = $this->handleTransaction(function () use ($request) {
            return $this->procurement->save($request);
        });

        return redirect()->route('procurement.index')->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    public function edit($id, Request $request)
    {
        $request->merge(['option' => $request->option ?: 'edit']);

        return $this->show($id, $request);
    }



     public function update($id, Request $request) {
        if (in_array($request->option, ['edit', 'review', 'approve'], true)) {
            $this->procurement->validateProcurementBudgetAvailability($request);
        }

        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->procurement->updateByOption($id, $request);
        });

        return redirect()->route('procurement.index')->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    public function destroy($id)
    {
        $result = $this->handleTransaction(function () use ($id) {
            return $this->procurement->destroy($id);
        });

        return redirect()->route('procurement.index')->with([
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
        if ($request->option === 'comments') {
            return $this->view->commentThread($id);
        }
        else{
            return $this->view->show($id, $request);
        }

    }


}
