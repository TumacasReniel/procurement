<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class ProcurementBACResource extends JsonResource
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
            'type'  => $this->type,
            'body' => $this->body,
            'procurement' => $this->whenLoaded('procurement'),
            'created_by'  =>  $this->created_by,
            'approved_by'  => $this->approved_by ,
            'status'  => $this->status,
            'created_at' => (new \DateTime($this->created_at))->format('F j, Y'),
        ];
    }
}
