<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class ProcurementNoaPoResource extends JsonResource
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
            'po_date'  => $this->po_date,
            'date_of_delivery'  => $this->date_of_delivery,
            'place_of_delivery'  => $this->place_of_delivery,
            'payment_term'  => $this->payment_term,
            'delivery_term'  => $this->delivery_term,
            'created_by'  =>  $this->created_by,
            'approved_by'  => $this->approved_by ,
            'status'  => $this->status,
            'noa'  => $this->noa,
            'created_at' => (new \DateTime($this->created_at))->format('F j, Y'),
        ];
    }
}
