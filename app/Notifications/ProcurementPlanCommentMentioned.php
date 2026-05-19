<?php

namespace App\Notifications;

use App\Models\ProcurementApp;
use App\Models\ProcurementPpmp;
use App\Models\RequestComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProcurementPlanCommentMentioned extends Notification
{
    use Queueable;

    public function __construct(
        protected ProcurementPpmp|ProcurementApp $plan,
        protected RequestComment $comment,
        protected User $actor,
        protected string $reason = 'mention',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $actor = [
            'id' => $this->actor->id,
            'username' => $this->actor->username,
            'name' => $this->actor->profile?->full_name
                ?? $this->actor->profile?->fullname
                ?? $this->actor->username,
            'avatar' => $this->actor->profile?->avatar,
        ];

        return [
            'type' => 'procurement_plan_comment_notification',
            'reason' => $this->reason === 'owner' ? 'owner' : 'mention',
            'target_user' => [
                'id' => $notifiable->id ?? null,
            ],
            'procurement_plan' => [
                'id' => $this->plan->id,
                'code' => $this->plan->code,
                'ppmp_no' => $this->plan->ppmp_no_override ?? $this->plan->code,
                'purpose' => $this->plan instanceof ProcurementPpmp ? $this->plan->purpose : null,
                'title' => $this->plan->title,
                'plan_type' => $this->plan instanceof ProcurementApp
                    ? 'APP'
                    : ($this->plan->plan_type_override ?? $this->plan->reference_app?->name),
            ],
            'comment' => [
                'id' => $this->comment->id,
                'content' => $this->comment->content,
                'created_at' => $this->comment->created_at,
            ],
            'actor' => $actor,
            'mentioned_by' => $actor,
        ];
    }
}
