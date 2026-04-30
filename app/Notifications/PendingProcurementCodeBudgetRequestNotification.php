<?php

namespace App\Notifications;

use App\Models\ProcurementCodeBudgetLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PendingProcurementCodeBudgetRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ProcurementCodeBudgetLog $budgetLog,
        protected User $actor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->budgetLog->loadMissing('procurement_code', 'source_procurement_code');

        $actor = [
            'id' => $this->actor->id,
            'username' => $this->actor->username,
            'name' => $this->actor->profile?->full_name
                ?? $this->actor->profile?->fullname
                ?? $this->actor->username,
            'avatar' => $this->actor->profile?->avatar,
        ];

        return [
            'type' => 'procurement_code_budget_request',
            'reason' => 'budget_review_required',
            'target_roles' => ['Budget Officer'],
            'message' => sprintf(
                'PAP code %s has a pending budget request for Budget Officer review.',
                $this->budgetLog->procurement_code?->code ?? 'request'
            ),
            'budget_request' => [
                'id' => $this->budgetLog->id,
                'amount' => $this->budgetLog->amount,
                'request_type' => $this->budgetLog->request_type,
                'description' => $this->budgetLog->description,
                'created_at' => $this->budgetLog->created_at,
            ],
            'procurement_code' => [
                'id' => $this->budgetLog->procurement_code?->id,
                'code' => $this->budgetLog->procurement_code?->code,
                'title' => $this->budgetLog->procurement_code?->title,
            ],
            'source_procurement_code' => [
                'id' => $this->budgetLog->source_procurement_code?->id,
                'code' => $this->budgetLog->source_procurement_code?->code,
                'title' => $this->budgetLog->source_procurement_code?->title,
            ],
            'actor' => $actor,
            'mentioned_by' => $actor,
        ];
    }
}
