<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryDashboardRequest;
use App\Http\Resources\Inventory\InventoryDashboardResource;
use App\Services\DropdownClass;
use App\Services\Inventory\DashboardClass;

class DashboardController extends Controller
{
    public $dropdown, $dashboard;

    public function __construct(
        DropdownClass $dropdown,
        DashboardClass $dashboard,
    ) {
        $this->dropdown = $dropdown;
        $this->dashboard = $dashboard;
    }

    public function index(InventoryDashboardRequest $request)
    {
        $dashboard = InventoryDashboardResource::make(
            $this->dashboard->dashboard([
                'period' => $request->input('period', 'monthly'),
            ])
        )->resolve();

        return inertia('Modules/Inventory/Dashboard', [
            'dropdowns' => [
                'roles' => \Auth::user()->roles,
                'designation' => \Auth::user()->org_chart?->designation,
                'categories' => $this->dropdown->dropdowns('Inventory Category'),
                'units' => $this->dropdown->dropdowns('Unit'),
                'locations' => $this->dropdown->dropdowns('Location'),
                'periods' => [
                    ['value' => 'monthly', 'name' => 'Monthly'],
                    ['value' => 'quarterly', 'name' => 'Quarterly'],
                    ['value' => 'yearly', 'name' => 'Yearly'],
                ],
            ],
            'totalItems' => $dashboard['totalItems'],
            'lowStockItems' => $dashboard['lowStockItems'],
            'outOfStock' => $dashboard['outOfStock'],
            'totalQuantity' => $dashboard['totalQuantity'],
            'totalStocks' => $dashboard['totalStocks'],
            'receivingsCount' => $dashboard['receivingsCount'],
            'withdrawalsCount' => $dashboard['withdrawalsCount'],
            'byCategory' => $dashboard['byCategory'],
            'recent' => $dashboard['recent'],
            'byStatus' => $dashboard['byStatus'],
            'filters' => $dashboard['filters'],
        ]);
    }
}
