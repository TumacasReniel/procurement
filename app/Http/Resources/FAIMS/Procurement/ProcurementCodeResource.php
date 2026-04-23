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
        $allocatedBudget = (float) $this->allocated_budget;
        $remainingBudget = (float) ($this->remaining_budget ?? $this->allocated_budget);
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
}
