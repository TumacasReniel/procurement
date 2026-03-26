<?php

namespace App\Http\Resources\FAIMS\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceCreditorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'account_code' => $this->account_code,
            'creditorable' => $this->creditorable ? [
                'id' => $this->creditorable->id,
                'name' => $this->creditorable->name ?? $this->creditorable->username ?? $this->name,
                'account_code' => $this->creditorable->account_code ?? $this->creditorable->code ?? $this->account_code,
                'type' => class_basename($this->creditorable),
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
?>

