<?php

namespace App\Http\Resources\FAIMS\Procurement;

use App\Models\ListStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementCodeBudgetLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeLabel = match ($this->type) {
            'approval_deduction' => 'Budget Deduction',
            'budget_increase' => 'Budget Increase',
            default => Str::headline($this->type),
        };
        $actualAwardedAmount = $this->actualAwardedAmount();
        $excessFundsAmount = $this->type === 'approval_deduction'
            ? round((float) $this->amount - $actualAwardedAmount, 2)
            : null;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $typeLabel,
            'status' => $this->status,
            'status_label' => Str::headline($this->status ?: 'approved'),
            'amount' => $this->amount,
            'actual_awarded_amount' => $actualAwardedAmount,
            'excess_funds_amount' => $excessFundsAmount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'attachment_name' => $this->attachment_name,
            'attachment_url' => $this->attachment_path ? asset('storage/' . ltrim($this->attachment_path, '/')) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'amount_direction' => $this->type === 'approval_deduction' ? 'decrease' : 'increase',
            'procurement' => $this->procurement ? [
                'id' => $this->procurement->id,
                'code' => $this->procurement->code,
                'title' => $this->procurement->title,
                'status' => $this->procurement->status?->name,
            ] : null,
            'processed_by' => $this->processed_by ? [
                'id' => $this->processed_by->id,
                'name' => $this->processed_by->profile?->full_name
                    ?? $this->processed_by->profile?->fullname
                    ?? $this->processed_by->name,
            ] : null,
            'requested_by' => $this->requested_by ? [
                'id' => $this->requested_by->id,
                'name' => $this->requested_by->profile?->full_name
                    ?? $this->requested_by->profile?->fullname
                    ?? $this->requested_by->name,
            ] : null,
            'reviewed_by' => $this->reviewed_by ? [
                'id' => $this->reviewed_by->id,
                'name' => $this->reviewed_by->profile?->full_name
                    ?? $this->reviewed_by->profile?->fullname
                    ?? $this->reviewed_by->name,
            ] : null,
        ];
    }

    private function actualAwardedAmount(): float
    {
        if ($this->type !== 'approval_deduction' || !$this->procurement_id) {
            return 0.0;
        }

        $completedItemStatusId = ListStatus::getID('Completed', 'Procurement');

        if (!$completedItemStatusId) {
            return 0.0;
        }

        $procurementTotal = DB::table('procurement_items')
            ->where('procurement_id', $this->procurement_id)
            ->selectRaw('COALESCE(SUM(total_cost), 0) as amount')
            ->value('amount');

        if ((float) $procurementTotal <= 0) {
            return 0.0;
        }

        $awardedAmount = DB::table('procurement_items')
            ->join('procurement_quotation_items', 'procurement_items.id', '=', 'procurement_quotation_items.procurement_item_id')
            ->join('procurement_bac_noa_items', 'procurement_quotation_items.id', '=', 'procurement_bac_noa_items.item_id')
            ->where('procurement_items.procurement_id', $this->procurement_id)
            ->where('procurement_items.status_id', $completedItemStatusId)
            ->selectRaw('COALESCE(SUM(procurement_quotation_items.bid_price * procurement_items.item_quantity), 0) as amount')
            ->value('amount');

        return round((float) $awardedAmount * ((float) $this->amount / (float) $procurementTotal), 2);
    }
}
