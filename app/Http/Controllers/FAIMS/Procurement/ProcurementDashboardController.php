<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Services\FAIMS\Procurement\ProcurementClass;
use Illuminate\Http\Request;

class ProcurementDashboardController extends Controller
{
    public function __construct(protected ProcurementClass $procurement)
    {
    }

    public function index(Request $request)
    {
        return inertia('Modules/FAIMS/Procurement/Dashboard', $this->procurement->dashboardPageProps());
    }
}
