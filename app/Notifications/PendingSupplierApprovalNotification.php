<?php

namespace App\Notifications;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PendingSupplierApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Supplier $supplier,
        protected User $actor,
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
            'type' => 'pending_supplier_approval',
            'reason' => 'approval_required',
            'message' => sprintf(
                'Supplier "%s" is waiting for Procurement Officer approval.',
                $this->supplier->name
            ),
            'supplier' => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'code' => $this->supplier->code,
                'approval_status' => $this->supplier->approval_status,
                'created_at' => $this->supplier->created_at,
            ],
            'actor' => $actor,
            'mentioned_by' => $actor,
        ];
    }
}
