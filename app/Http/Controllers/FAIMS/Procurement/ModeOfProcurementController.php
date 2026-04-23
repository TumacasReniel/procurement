<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ModeOfProcurementRequest;
use App\Services\FAIMS\Procurement\ModeOfProcurementClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ModeOfProcurementController extends Controller
{
    use HandlesTransaction;

    public function __construct(
        protected ModeOfProcurementClass $mode_of_procurement
    ) {
    }

    public function index(Request $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->mode_of_procurement->lists($request);

            default:
                return inertia('Modules/FAIMS/Procurement/ModesOfProcurement/Index');
        }
    }

    public function store(ModeOfProcurementRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->mode_of_procurement->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function show($id)
    {
        return $this->mode_of_procurement->show($id);
    }

    public function update(ModeOfProcurementRequest $request, $id)
    {
        $result = $this->handleTransaction(function () use ($request, $id) {
            return $this->mode_of_procurement->update($request, $id);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function destroy($id)
    {
        $result = $this->handleTransaction(function () use ($id) {
            return $this->mode_of_procurement->destroy($id);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
