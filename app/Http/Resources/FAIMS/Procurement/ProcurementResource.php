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
            'pr_no' =>  $this->code,
            'date' => (new \DateTime($this->date))->format('F j, Y'),
            'date_iso' => $this->date,
            'created_at' => $this->created_at?->toDateTimeString(),
            'calendar_start_at' => $this->created_at?->toDateTimeString(),
            'purpose' =>  $this->purpose,
            'title' =>  $this->title,
            'unit' =>  $this->unit,
            'division' =>  $this->division,
            'fund_cluster' =>  $this->fund_cluster,
            'fund_cluster_name' => $this->fund_cluster?->name,
            'classification_id' => $this->classification_id,
            'classification' => $this->classification,
            'reference_app_id' => $this->reference_app_id,
            'reference_app' => $this->reference_app,
            'procurement_app_id' => $this->procurement_app_id,
            'procurement_app' => $this->procurement_app,
            'created_by' => $this->created_by->profile->full_name ,
            'created_by_id' => $this->created_by_id,
            'requested_by' => $this->requested_by->profile->full_name ,
            'requested_by_id' => $this->requested_by_id,
            'approved_by' =>  $this->approved_by->profile->full_name ,
            'approved_by_id' => $this->approved_by_id,
            'codes' =>  $this->codes,
            'items' =>  $this->items,
            'is_create_by_category' => $this->isCreateByCategory(),
            'total_amount' => round((float) $this->items->sum('total_cost'), 2),
            'quotation_count'  => $this->quotation_count,
            'reawarded_count'  => $this->reawarded_count,
            'rebidded_count'  => $this->rebidded_count,
            'comments_count' => $this->comments_count ?? 0,
            'status' =>  $this->status,
            'sub_status' =>  $this->sub_status,
        ];
    }

    protected function isCreateByCategory(): bool
    {
        if (!$this->unit_id) {
            return false;
        }

        return $this->items
            ->contains(fn ($item) => $item->ppmp_item?->ppmp?->unit_id
                && (int) $item->ppmp_item->ppmp->unit_id !== (int) $this->unit_id);
    }
}
