<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcurementAssignmentRequest;
use App\Models\ProcurementAssignment;
use App\Services\DropdownClass;
use App\Services\FAIMS\Procurement\ProcurementAssignmentClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ProcurementAssignmentController extends Controller
{
    use HandlesTransaction;

    public function __construct(
        public DropdownClass $dropdown,
        public ProcurementAssignmentClass $assignment,
    )
    {
    }

    public function index(Request $request)
    {
        if (
            !$request->boolean('json') &&
            !$request->expectsJson()
        ) {
            return inertia('Modules/FAIMS/Procurement/Assignments', [
                'statuses' => $this->dropdown->statuses('Procurement'),
            ]);
        }

        return $this->assignment->lists();
    }

    public function store(ProcurementAssignmentRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->assignment->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function update(ProcurementAssignmentRequest $request, ProcurementAssignment $procurement_assignment)
    {
        $result = $this->handleTransaction(function () use ($request, $procurement_assignment) {
            return $this->assignment->update($request, $procurement_assignment);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function destroy(ProcurementAssignment $procurement_assignment)
    {
        $result = $this->handleTransaction(function () use ($procurement_assignment) {
            return $this->assignment->destroy($procurement_assignment);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
