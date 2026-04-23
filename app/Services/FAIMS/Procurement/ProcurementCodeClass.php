<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\User;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeBudgetLog;
use App\Models\ProcurementCodeUnit;
use App\Http\Resources\FAIMS\Procurement\ProcurementCodeResource;
use App\Http\Resources\FAIMS\Procurement\ProcurementCodeBudgetLogResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class ProcurementCodeClass
{
    public function lists($request){
        $data = ProcurementCodeResource::collection(
            ProcurementCode::query()
            ->with('end_users.end_user', 'app_type', 'mode_of_procurement')
            ->withCount($this->budgetLogCountMap())
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery->where('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('code', 'LIKE', "%{$keyword}%");
                });
            })
            ->orderBy('created_at','DESC')
            ->paginate($request->count ?: 10)
        );
        return $data;
    }

    public function save($request)
    {
        // Create the PAP Code with the correct syntax
        $procurement_code = ProcurementCode::create($request->only(
                'title', 
                'code', 
                'year', 
                'allocated_budget',
                'app_type_id',
                'mode_of_procurement_id'
            )
        );

        $procurement_code->remaining_budget = $request->allocated_budget;
        $procurement_code->save();

        // Loop through end_user_ids and save them
        foreach ($request->end_user_ids as $end_user_id) {
            ProcurementCodeUnit::create([
                'procurement_code_id' => $procurement_code->id,
                'end_user_id' => $end_user_id,
            ]);
        }

        // Wrap the newly created PAPCode in a Resource
        return [
            'data' => new ProcurementCodeResource($procurement_code),
            'message' => 'PAP Code created successfully!',
            'info' => "You've successfully added new PAP Code.",
        ];
    }

    public function update($request, $id)
    {
        $procurement_code = ProcurementCode::query()
            ->withCount([
                'budget_logs as deduction_logs_count' => function ($query) {
                    $query->where('type', 'approval_deduction');
                },
                'budget_logs as approved_budget_increase_logs_count' => function ($query) {
                    $query->where('type', 'budget_increase')
                        ->where('status', 'approved');
                },
            ])
            ->findOrFail($id);

        if (
            (int) $procurement_code->deduction_logs_count > 0 ||
            (int) $procurement_code->approved_budget_increase_logs_count > 0
        ) {
            throw new \Exception('This PAP code can no longer be edited because it already has approved budget movement history.');
        }

        // Update the PAP Code with the correct syntax
        $procurement_code->update($request->only(
                'title', 
                'code', 
                'year', 
                'allocated_budget',
                'app_type_id',
                'mode_of_procurement_id'
            )
        );

        $procurement_code->remaining_budget = (float) $request->allocated_budget;
        $procurement_code->save();

        // Sync end_user_ids
        $procurement_code->end_users()->delete(); // Remove existing associations
        foreach ($request->end_user_ids as $end_user_id) {
            ProcurementCodeUnit::create([
                'procurement_code_id' => $procurement_code->id,
                'end_user_id' => $end_user_id,
            ]);
        }

        // Wrap the updated PAPCode in a Resource
        return [
            'data' => new ProcurementCodeResource($this->loadProcurementCode($procurement_code->id)),
            'message' => 'PAP Code updated successfully!',
            'info' => "You've successfully updated the PAP Code.",
        ];
    }

    public function profile($id): array
    {
        return $this->profilePayload($this->loadProcurementCode($id));
    }

    public function requestBudgetIncrease($id, $request): array
    {
        $procurementCode = ProcurementCode::query()
            ->lockForUpdate()
            ->findOrFail($id);

        $currentBalanceCents = $this->amountToCents(
            $procurementCode->remaining_budget ?? $procurementCode->allocated_budget
        );
        $requestedAmountCents = $this->amountToCents($request->amount);
        /** @var UploadedFile|null $attachment */
        $attachment = $request->file('attachment');

        ProcurementCodeBudgetLog::create([
            'procurement_code_id' => $procurementCode->id,
            'procurement_id' => null,
            'processed_by_id' => null,
            'requested_by_id' => Auth::id(),
            'reviewed_by_id' => null,
            'type' => 'budget_increase',
            'status' => 'pending',
            'amount' => $this->centsToAmount($requestedAmountCents),
            'balance_before' => $this->centsToAmount($currentBalanceCents),
            'balance_after' => $this->centsToAmount($currentBalanceCents),
            'description' => $request->description,
            'attachment_name' => $attachment?->getClientOriginalName(),
            'attachment_path' => $attachment?->store('procurement_code_budget_bases', 'public'),
            'reviewed_at' => null,
        ]);

        return [
            'data' => $this->profilePayload($this->loadProcurementCode($procurementCode->id)),
            'message' => 'Budget increase request submitted successfully!',
            'info' => 'The request is now waiting for Budget Officer approval.',
        ];
    }

    public function approveBudgetIncrease($id, $logId): array
    {
        $procurementCode = ProcurementCode::query()
            ->lockForUpdate()
            ->findOrFail($id);

        $log = ProcurementCodeBudgetLog::query()
            ->where('procurement_code_id', $procurementCode->id)
            ->where('id', $logId)
            ->where('type', 'budget_increase')
            ->lockForUpdate()
            ->firstOrFail();

        if ($log->status !== 'pending') {
            throw new \Exception('Only pending budget increase requests can be approved.');
        }

        $balanceBeforeCents = $this->amountToCents(
            $procurementCode->remaining_budget ?? $procurementCode->allocated_budget
        );
        $requestedAmountCents = $this->amountToCents($log->amount);
        $balanceAfterCents = $balanceBeforeCents + $requestedAmountCents;
        $allocatedBudgetAfterCents = $this->amountToCents($procurementCode->allocated_budget) + $requestedAmountCents;

        $procurementCode->allocated_budget = $this->centsToAmount($allocatedBudgetAfterCents);
        $procurementCode->remaining_budget = $this->centsToAmount($balanceAfterCents);
        $procurementCode->save();

        $log->update([
            'processed_by_id' => Auth::id(),
            'reviewed_by_id' => Auth::id(),
            'status' => 'approved',
            'balance_before' => $this->centsToAmount($balanceBeforeCents),
            'balance_after' => $this->centsToAmount($balanceAfterCents),
            'reviewed_at' => now(),
        ]);

        return [
            'data' => $this->profilePayload($this->loadProcurementCode($procurementCode->id)),
            'message' => 'Budget increase request approved successfully!',
            'info' => 'The PAP code budget has been updated and recorded in history.',
        ];
    }

    public function rejectBudgetIncrease($id, $logId): array
    {
        $procurementCode = ProcurementCode::query()
            ->lockForUpdate()
            ->findOrFail($id);

        $log = ProcurementCodeBudgetLog::query()
            ->where('procurement_code_id', $procurementCode->id)
            ->where('id', $logId)
            ->where('type', 'budget_increase')
            ->lockForUpdate()
            ->firstOrFail();

        if ($log->status !== 'pending') {
            throw new \Exception('Only pending budget increase requests can be rejected.');
        }

        $currentBalanceCents = $this->amountToCents(
            $procurementCode->remaining_budget ?? $procurementCode->allocated_budget
        );

        $log->update([
            'processed_by_id' => null,
            'reviewed_by_id' => Auth::id(),
            'status' => 'rejected',
            'balance_before' => $this->centsToAmount($currentBalanceCents),
            'balance_after' => $this->centsToAmount($currentBalanceCents),
            'reviewed_at' => now(),
        ]);

        return [
            'data' => $this->profilePayload($this->loadProcurementCode($procurementCode->id)),
            'message' => 'Budget increase request rejected.',
            'info' => 'The request remains in history for audit purposes.',
        ];
    }

    protected function loadProcurementCode($id): ProcurementCode
    {
        return ProcurementCode::query()
            ->with([
                'end_users.end_user',
                'app_type',
                'mode_of_procurement',
                'budget_logs' => function ($query) {
                    $query->with([
                        'procurement.status',
                        'processed_by.profile',
                        'requested_by.profile',
                        'reviewed_by.profile',
                    ])->latest();
                },
            ])
            ->withCount($this->budgetLogCountMap())
            ->findOrFail($id);
    }

    protected function profilePayload(ProcurementCode $procurementCode): array
    {
        return [
            'data' => (new ProcurementCodeResource($procurementCode))->resolve(),
            'logs' => ProcurementCodeBudgetLogResource::collection(
                $procurementCode->budget_logs
            )->resolve(),
        ];
    }

    protected function budgetLogCountMap(): array
    {
        return [
            'budget_logs',
            'budget_logs as deduction_logs_count' => function ($query) {
                $query->where('type', 'approval_deduction');
            },
            'budget_logs as approved_budget_increase_logs_count' => function ($query) {
                $query->where('type', 'budget_increase')
                    ->where('status', 'approved');
            },
            'budget_logs as pending_budget_increase_requests_count' => function ($query) {
                $query->where('type', 'budget_increase')
                    ->where('status', 'pending');
            },
        ];
    }

    public function canManageProcurementCodes(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('Procurement Officer') || $user->hasRole('Administrator');
    }

    public function canViewProcurementCodes(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->canManageProcurementCodes($user)
            || $user->hasRole('Procurement Staff')
            || $user->hasRole('Budget Officer');
    }

    public function canRequestBudgetIncrease(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('Procurement Staff')
            || $user->hasRole('Procurement Officer')
            || $user->hasRole('Administrator');
    }

    public function canReviewBudgetIncrease(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('Budget Officer') || $user->hasRole('Administrator');
    }

    protected function amountToCents($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    protected function centsToAmount(int $amountInCents): float
    {
        return round($amountInCents / 100, 2);
    }
}
