<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModeOfProcurementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $procurementCodesCount = (int) ($this->procurement_codes_count ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'classification' => $this->classification,
            'type' => $this->type,
            'section_reference' => $this->type !== 'n/a' ? $this->type : null,
            'others' => $this->others,
            'legal_basis' => $this->others !== 'n/a' ? $this->others : null,
            'is_active' => (bool) $this->is_active,
            'procurement_codes_count' => $procurementCodesCount,
            'can_delete' => $procurementCodesCount === 0,
        ];
    }
}
