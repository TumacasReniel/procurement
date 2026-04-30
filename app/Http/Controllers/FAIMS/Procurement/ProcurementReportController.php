<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\FAIMS\Procurement\ProcurementClass;
use Illuminate\Http\Request;

class ProcurementReportController extends Controller
{
    public function __construct(
        protected PrintClass $print,
        protected ProcurementClass $procurement,
    ) {
    }

    public function index(Request $request)
    {
        if ($request->option === 'print') {
            return $this->print->printReport($request);
        }

        return inertia('Modules/FAIMS/Procurement/Reports/Index', $this->procurement->reportPageProps());
    }
}
