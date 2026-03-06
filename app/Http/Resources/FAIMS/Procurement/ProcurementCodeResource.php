<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcurementCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'title'=> $this->title,
            'code' =>  $this->code,
            'allocated_budget'=> $this->allocated_budget,
            'mode_of_procurement' => $this->mode_of_procurement,
            'app_type' => $this->app_type,
            'end_users' => $this->end_users,
            'year' => $this->year,
        ];
    }
}
