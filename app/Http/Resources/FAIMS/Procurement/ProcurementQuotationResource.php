<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class ProcurementQuotationResource extends JsonResource
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
            'date' => (new \DateTime($this->created_at))->format('F j, Y'),
            'code'  => $this->code,
            'submission_not_later_than' =>(new \DateTime($this->submission_not_later_than))->format('F j, Y'),
            'supplier'  => $this->supplier,
            'supply_officer'  => $this->supply_officer->profile->full_name ,
            'status'  => $this->status,
        ];
    }
}
