<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcurementPPMPListRequest;
use App\Http\Requests\Procurement\ProcurementPPMPPlanRequest;
use App\Http\Requests\Procurement\ProcurementPPMPUpdateRequest;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\FAIMS\Procurement\ProcurementPPMPClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ProcurementPPMPController extends Controller
{
    use HandlesTransaction;

    public $ppmp, $print;

    public function __construct(
        ProcurementPPMPClass $ppmp,
        PrintClass $print,
    ) {
        $this->ppmp = $ppmp;
        $this->print = $print;
    }

    public function index(ProcurementPPMPListRequest $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->ppmp->lists($request);

            case 'available_units':
                return $this->ppmp->availablePpmpUnits($request);

            default:
                return inertia('Modules/FAIMS/Procurement/PPMP/Index', $this->ppmp->indexPageProps());
        }
    }

    public function store(ProcurementPPMPPlanRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->ppmp->store($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function update($id, ProcurementPPMPUpdateRequest $request)
    {
        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->ppmp->updateByOption($id, $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function show($id, Request $request)
    {
        if ($request->type) {
            return $this->print->print($id, $request);
        }

        return inertia('Modules/FAIMS/Procurement/PPMP/View', $this->ppmp->showPageProps($id, $request));
    }

}
