<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcurementRequestChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $procurement,
        public string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('procurement-requests'),
        ];

        if (!empty($this->procurement['id'])) {
            $channels[] = new PrivateChannel('procurement.' . $this->procurement['id']);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'procurement-request.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'procurement' => $this->procurement,
        ];
    }
}
