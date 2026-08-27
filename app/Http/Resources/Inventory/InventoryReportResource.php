<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title_id' => $this->title_id,
            'title' => $this->title?->name,
            'category_id' => $this->category_id,
            'category' => $this->category?->name,
            'period_type' => $this->period_type,
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
            'period_quarter' => $this->period_quarter,
            'period_start' => optional($this->period_start)->format('Y-m-d'),
            'period_end' => optional($this->period_end)->format('Y-m-d'),
            'period_label' => $this->period_label,
            'created_by' => $this->creator?->profile?->fullname ?? $this->creator?->username,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
