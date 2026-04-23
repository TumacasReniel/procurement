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
            'type' => $this->procurement_bac?->type,
            'body' => $this->remarks,
            'remarks' => $this->remarks,
            'procurement_id' => $this->procurement_id,
            'bac_resolution'  => $this->procurement_bac,
            'procurement' => $this->procurement_bac ? new ProcurementResource($this->procurement_bac->procurement) : null,
            'procurement_quotation' => $this->procurement_quotation,
            'items' => $this->items,
            'created_by'  =>  $this->created_by,
            'approved_by'  => $this->approved_by ,
            'served_at' => $this->served_at?->format('F j, Y'),
            'conformed_at' => $this->conformed_at?->format('F j, Y'),
            'status'  => $this->status,
            'created_at' => (new \DateTime($this->created_at))->format('F j, Y'),
        ];
    }
}
