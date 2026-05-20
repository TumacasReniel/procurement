<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcurementPlanStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $plan
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('procurement-plans'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'procurement-plan.status-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'plan' => $this->plan,
        ];
    }
}
