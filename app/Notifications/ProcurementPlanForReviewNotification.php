<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProcurementPlanForReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected object $plan,
        protected User $actor,
        protected string $planType,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $relations = ['status'];

        if (method_exists($this->plan, 'unit')) {
            $relations[] = 'unit';
        }

        $this->plan->loadMissing($relations);

        $actor = [
            'id' => $this->actor->id,
            'username' => $this->actor->username,
            'name' => $this->actor->profile?->full_name
                ?? $this->actor->profile?->fullname
                ?? $this->actor->username,
            'avatar' => $this->actor->profile?->avatar,
        ];

        $planCode = $this->plan->code
            ?? $this->plan->ppmp_no_override
            ?? $this->plan->ppmp_no
            ?? "{$this->planType} #{$this->plan->id}";
        $unitName = method_exists($this->plan, 'unit') ? ($this->plan->unit?->name ?? null) : null;

        return [
            'type' => 'procurement_plan_for_review',
            'reason' => 'plan_review_required',
            'target_roles' => ['Budget Officer'],
            'message' => "{$this->planType} {$planCode} is now for Budget Officer review.",
            'procurement_plan' => [
                'id' => $this->plan->id,
                'plan_type' => $this->planType,
                'code' => $planCode,
                'ppmp_no' => $this->plan->ppmp_no_override ?? null,
                'title' => $this->plan->title ?? null,
                'purpose' => $this->plan->purpose ?? null,
                'status' => $this->plan->status?->name,
                'unit' => $unitName,
                'year' => $this->plan->year
                    ?? ($this->plan->date ? date('Y', strtotime((string) $this->plan->date)) : null),
            ],
            'actor' => $actor,
            'mentioned_by' => $actor,
        ];
    }
}
