<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Services\FAIMS\Procurement\IAReportClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class IAReportController extends Controller
{
    use HandlesTransaction;

    public function __construct(
        protected IAReportClass $ia_report
    ) {
    }

    public function index(Request $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->ia_report->lists($request);

            default:
                return inertia('Modules/FAIMS/Procurement/IAReport/Index', [
                    'mode' => $request->is('faims/receiving-deliveries') ? 'receiving' : 'iar',
                ]);
        }
    }

    public function show($id, Request $request){
        if($request->type){
            return $this->print->print($id, $request);
        }
        else{
            return $this->view->show($id, $request);
        }
        
    }

    public function update(Request $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            switch ($request->option) {
                case 'mark_inspected':
                    return $this->ia_report->mark_inspected($request);
                case 'edit':
                    return $this->ia_report->mark_inspected($request);
                default:
                    return inertia('Modules/FAIMS/Procurement/IAReport/Index');
            }
        });

         return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

}
