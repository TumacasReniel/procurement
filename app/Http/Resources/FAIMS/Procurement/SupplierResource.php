<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class SupplierResource extends JsonResource
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
            'name'  => $this->name,
            'code'  => $this->code,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'conformes'  => $this->conformes,
            'address'  => $this->address ? $this->address->address : null,
            'attachments'  => $this->attachments,
        ];
    }
}
