<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\DashboardClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardClass $dashboard)
    {
        $period = (string) $request->query('period', 'monthly');
        $data = $dashboard->dashboard([
            'period' => $period,
        ]);

        return Inertia::render('Modules/Inventory/Dashboard', [
            'roles' => Auth::user()->roles ?? [],
            'totalItems' => $data['totalItems'],
            'lowStockItems' => $data['lowStockItems'],
            'outOfStock' => $data['outOfStock'],
            'totalQuantity' => $data['totalQuantity'],
            'byCategory' => $data['byCategory'],
            'recent' => $data['recent'],
            'byStatus' => $data['byStatus'],
            'categories' => $data['categories'],
            'units' => $data['units'],
            'locations' => $data['locations'],
            'filters' => [
                'period' => $data['period'] ?? 'monthly',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ],
        ]);
    }
}
