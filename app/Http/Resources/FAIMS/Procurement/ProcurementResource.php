<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ProcurementResource extends JsonResource
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
            'code' =>  $this->code,
            'date' => (new \DateTime($this->date))->format('F j, Y'),
            'purpose' =>  $this->purpose,
            'title' =>  $this->title,
            'unit' =>  $this->unit,
            'division' =>  $this->division,
            'fund_cluster' =>  $this->fund_cluster,
            'classification_id' => $this->classification_id,
            'classification' => $this->classification,
            'created_by' => $this->created_by->profile->full_name ,
            'created_by_id' => $this->created_by_id,
            'requested_by' => $this->requested_by->profile->full_name ,
            'requested_by_id' => $this->requested_by_id,
            'approved_by' =>  $this->approved_by->profile->full_name ,
            'approved_by_id' => $this->approved_by_id,
            'codes' =>  $this->codes,
            'items' =>  $this->items,
            'quotation_count'  => $this->quotation_count,
            'reawarded_count'  => $this->reawarded_count,
            'rebidded_count'  => $this->rebidded_count,
            'comments_count' => $this->comments_count ?? 0,
            'status' =>  $this->status,
            'sub_status' =>  $this->sub_status,
        ];
    }
}
