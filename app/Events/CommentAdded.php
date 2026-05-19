<?php

namespace App\Events;

use App\Models\RequestComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(RequestComment $comment)
    {
        $this->comment = $comment->load('user.profile');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->channelName()),
        ];
    }

    protected function channelName(): string
    {
        return match ($this->comment->commentable_type) {
            'App\\Models\\ProcurementApp' => 'procurement-plan-app.' . $this->comment->commentable_id,
            'App\\Models\\ProcurementPpmp' => 'procurement-plan.' . $this->comment->commentable_id,
            default => 'procurement.' . $this->comment->commentable_id,
        };
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'comment.added';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => $this->comment,
        ];
    }
}
