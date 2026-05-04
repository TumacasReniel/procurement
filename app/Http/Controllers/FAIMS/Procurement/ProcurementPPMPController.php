<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcurementPPMPListRequest;
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
    )
    {
        $this->ppmp = $ppmp;
        $this->print = $print;
    }

    public function index(ProcurementPPMPListRequest $request)
    {
        if ($request->option === 'lists') {
            return $this->ppmp->lists($request);
        }

        if ($request->option === 'available_units') {
            return $this->ppmp->available_ppmp_units($request);
        }

        return inertia('Modules/FAIMS/Procurement/PPMP/Index', $this->ppmp->index_page_props());
    }

    public function store(Request $request)
    {
        $this->ensure_can_manage();

        if ($request->option === 'create_unit_ppmp') {
            return $this->create_unit_ppmp($request);
        }

        $request->validate([
            'unit_id' => ['nullable', 'integer', 'exists:list_units,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
            'plan_type' => ['required', 'in:annual,supplemental'],
        ], [
            'year.required' => 'Please select a plan year.',
            'plan_type.required' => 'Please select the APP type.',
        ]);

        $result = $this->handleTransaction(function () use ($request) {
            return $this->ppmp->create_plan($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function update($id, Request $request)
    {
        return match ($request->option) {
            'submit_final' => $this->submit_final($id, $request),
            'add_item' => $this->add_item($id, $request),
            default => abort(404),
        };
    }

    protected function create_unit_ppmp(Request $request)
    {
        $request->validate([
            'unit_id' => ['required', 'integer', 'exists:list_units,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 10)],
        ]);

        $result = $this->handleTransaction(function () use ($request) {
            return $this->ppmp->create_unit_ppmp($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    protected function submit_final($id, Request $request)
    {
        $this->ensure_can_mark_final_ppmp();

        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->ppmp->submit_final($id, $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    protected function add_item($id, Request $request)
    {
        $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'item_description' => ['required', 'string'],
            'item_quantity' => ['required', 'numeric', 'min:0.0001'],
            'item_unit_type_id' => ['required', 'integer', 'exists:unit_types,id'],
            'item_unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->ppmp->add_item($id, $request);
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

        return inertia('Modules/FAIMS/Procurement/PPMP/Show', $this->ppmp->show_page_props($id, $request));
    }

    protected function ensure_can_manage(): void
    {
        abort_unless(
            auth()->user()?->hasRole('Procurement Officer') || auth()->user()?->hasRole('Administrator'),
            403,
            'Only Procurement Officer or Administrator can manage PPMP APP plans.'
        );
    }

    protected function ensure_can_mark_final_ppmp(): void
    {
        abort_unless(
            auth()->user()?->hasRole('Procurement Officer'),
            403,
            'Only Procurement Officer can mark PPMP as final.'
        );
    }
}
