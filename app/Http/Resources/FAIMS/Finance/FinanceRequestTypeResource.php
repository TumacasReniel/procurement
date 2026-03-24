<?php

namespace App\Http\Resources\FAIMS\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceRequestTypeResource extends JsonResource
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
            'name' => $this->name,
            'default_text' => $this->default_text,
            'documents' => $this->documents,
            'is_active' => $this->is_active,
        ];
    }
}

