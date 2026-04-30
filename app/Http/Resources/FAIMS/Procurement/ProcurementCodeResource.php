<?php

namespace App\Http\Resources\FAIMS\Procurement;

use App\Models\ListStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ProcurementCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allocatedBudget = (float) $this->allocated_budget;
        $remainingBudget = (float) ($this->remaining_budget ?? $this->allocated_budget);
        $approvedBudget = $this->approvedBudgetAmount();
        $actualAwardedAmount = $this->actualAwardedAmount();
        $budgetLogsCount = isset($this->budget_logs_count) ? (int) $this->budget_logs_count : 0;
        $deductionLogsCount = isset($this->deduction_logs_count) ? (int) $this->deduction_logs_count : 0;
        $approvedBudgetIncreaseLogsCount = isset($this->approved_budget_increase_logs_count)
            ? (int) $this->approved_budget_increase_logs_count
            : 0;
        $pendingBudgetIncreaseRequestsCount = isset($this->pending_budget_increase_requests_count)
            ? (int) $this->pending_budget_increase_requests_count
            : 0;

        return [
            'id'=> $this->id,
            'title'=> $this->title,
            'code' =>  $this->code,
            'allocated_budget'=> $allocatedBudget,
            'remaining_budget' => $remainingBudget,
            'deducted_budget' => round($allocatedBudget - $remainingBudget, 2),
            'approved_budget_amount' => $approvedBudget,
            'actual_awarded_amount' => $actualAwardedAmount,
            'award_variance_amount' => round($approvedBudget - $actualAwardedAmount, 2),
            'is_over_allocated' => $remainingBudget < 0,
            'mode_of_procurement' => $this->mode_of_procurement,
            'app_type' => $this->app_type,
            'end_users' => $this->end_users,
            'year' => $this->year,
            'budget_logs_count' => $budgetLogsCount,
            'deduction_logs_count' => $deductionLogsCount,
            'approved_budget_increase_logs_count' => $approvedBudgetIncreaseLogsCount,
            'pending_budget_increase_requests_count' => $pendingBudgetIncreaseRequestsCount,
            'has_deductions' => $deductionLogsCount > 0,
            'can_edit' => $deductionLogsCount === 0 && $approvedBudgetIncreaseLogsCount === 0,
        ];
    }

    private function approvedBudgetAmount(): float
    {
        if ($this->relationLoaded('budget_logs')) {
            return round((float) $this->budget_logs
                ->where('type', 'approval_deduction')
                ->sum('amount'), 2);
        }

        return round((float) DB::table('procurement_code_budget_logs')
            ->where('procurement_code_id', $this->id)
            ->where('type', 'approval_deduction')
            ->sum('amount'), 2);
    }

    private function actualAwardedAmount(): float
    {
        $completedItemStatusId = ListStatus::getID('Completed', 'Procurement');

        if (!$completedItemStatusId) {
            return 0.0;
        }

        $procurementTotals = DB::table('procurement_items')
            ->select('procurement_id')
            ->selectRaw('SUM(total_cost) as approved_total')
            ->groupBy('procurement_id');

        return round((float) DB::table('procurement_code_budget_logs as budget_logs')
            ->joinSub($procurementTotals, 'procurement_totals', function ($join) {
                $join->on('budget_logs.procurement_id', '=', 'procurement_totals.procurement_id');
            })
            ->join('procurement_items', 'budget_logs.procurement_id', '=', 'procurement_items.procurement_id')
            ->join('procurement_quotation_items', 'procurement_items.id', '=', 'procurement_quotation_items.procurement_item_id')
            ->join('procurement_bac_noa_items', 'procurement_quotation_items.id', '=', 'procurement_bac_noa_items.item_id')
            ->where('budget_logs.procurement_code_id', $this->id)
            ->where('budget_logs.type', 'approval_deduction')
            ->where('procurement_items.status_id', $completedItemStatusId)
            ->selectRaw('COALESCE(SUM((procurement_quotation_items.bid_price * procurement_items.item_quantity) * (budget_logs.amount / NULLIF(procurement_totals.approved_total, 0))), 0) as amount')
            ->value('amount'), 2);
    }
}
