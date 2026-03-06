<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\FAIMS\Procurement\ProcurementResource;


class ProcurementBacNoaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code'  => $this->code,
            'bac_resolution'  => $this->procurement_bac,
            'procurement' => $this->procurement_bac ? new ProcurementResource($this->procurement_bac->procurement) : null,
            'procurement_quotation' => $this->procurement_quotation,
            'items' => $this->items,
            'created_by'  =>  $this->created_by,
            'approved_by'  => $this->approved_by ,
            'status'  => $this->status,
            'created_at' => (new \DateTime($this->created_at))->format('F j, Y'),
        ];
    }
}
