<?php

namespace App\Notifications;

use App\Models\Procurement;
use App\Models\RequestComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProcurementCommentMentioned extends Notification
{
    use Queueable;

    public function __construct(
        protected Procurement $procurement,
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
            'type' => 'procurement_comment_notification',
            'reason' => $this->reason === 'owner' ? 'owner' : 'mention',
            'procurement' => [
                'id' => $this->procurement->id,
                'code' => $this->procurement->code,
                'purpose' => $this->procurement->purpose,
            ],
            'comment' => [
                'id' => $this->comment->id,
                'content' => $this->comment->content,
                'created_at' => $this->comment->created_at,
            ],
            'actor' => $actor,
            // Keep the legacy key so the existing UI keeps working while we expand the payload.
            'mentioned_by' => $actor,
        ];
    }
}
