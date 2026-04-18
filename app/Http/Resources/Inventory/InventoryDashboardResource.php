<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totalItems' => (int) data_get($this->resource, 'totalItems', 0),
            'lowStockItems' => (int) data_get($this->resource, 'lowStockItems', 0),
            'outOfStock' => (int) data_get($this->resource, 'outOfStock', 0),
            'totalQuantity' => (int) data_get($this->resource, 'totalQuantity', 0),
            'totalStocks' => (int) data_get($this->resource, 'totalStocks', 0),
            'receivingsCount' => (int) data_get($this->resource, 'receivingsCount', 0),
            'withdrawalsCount' => (int) data_get($this->resource, 'withdrawalsCount', 0),
            'byCategory' => collect(data_get($this->resource, 'byCategory', []))->values()->all(),
            'recent' => collect(data_get($this->resource, 'recent', []))->values()->all(),
            'byStatus' => collect(data_get($this->resource, 'byStatus', []))->all(),
            'filters' => [
                'period' => data_get($this->resource, 'period', 'monthly'),
                'start_date' => data_get($this->resource, 'start_date'),
                'end_date' => data_get($this->resource, 'end_date'),
            ],
        ];
    }
}
