<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\User;
use App\Notifications\PendingProcurementCodeBudgetRequestNotification;
use App\Notifications\PendingSupplierApprovalNotification;
use App\Notifications\ProcurementCommentMentioned;
use App\Notifications\ProcurementPlanForReviewNotification;
use App\Notifications\ProcurementPlanCommentMentioned;
use Illuminate\Support\Facades\Schema;

class NotificationClass
{
    public function mentionNotifications($request): array
    {
        if (!Schema::hasTable('notifications')) {
            return [
                'data' => [],
                'meta' => [
                    'unread_count' => 0,
                    'has_more' => false,
                ],
            ];
        }

        if (!$request->user()) {
            return [
                'data' => [],
                'meta' => [
                    'unread_count' => 0,
                    'has_more' => false,
                ],
                '_status' => 401,
            ];
        }

        $limit = max(1, min((int) $request->input('limit', 4), 10));

        $query = $request->user()
            ->unreadNotifications()
            ->whereIn('type', $this->notificationTypes());

        $visibleNotifications = (clone $query)
            ->latest()
            ->get()
            ->filter(fn ($notification) => $this->procurementNotificationVisibleToUser($notification, $request->user()))
            ->values();

        $unreadCount = $visibleNotifications->count();

        $notifications = $visibleNotifications
            ->take($limit)
            ->map(fn ($notification) => $this->transformProcurementNotification($notification))
            ->filter()
            ->values();

        return [
            'data' => $notifications,
            'meta' => [
                'unread_count' => $unreadCount,
                'has_more' => $unreadCount > $notifications->count(),
            ],
        ];
    }

    public function markMentionNotificationRead(string $notificationId, $request): array
    {
        if (!Schema::hasTable('notifications')) {
            return ['status' => false];
        }

        if (!$request->user()) {
            return [
                'status' => false,
                '_status' => 401,
            ];
        }

        $notification = $request->user()
            ->notifications()
            ->whereIn('type', $this->notificationTypes())
            ->findOrFail($notificationId);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return ['status' => true];
    }

    protected function notificationTypes(): array
    {
        return [
            ProcurementCommentMentioned::class,
            ProcurementPlanCommentMentioned::class,
            ProcurementPlanForReviewNotification::class,
            PendingProcurementCodeBudgetRequestNotification::class,
            PendingSupplierApprovalNotification::class,
        ];
    }

    protected function procurementNotificationVisibleToUser($notification, User $user): bool
    {
        if ($notification->type === PendingSupplierApprovalNotification::class) {
            return $user->hasActiveRole(['Procurement Officer', 'Administrator']);
        }

        if ($notification->type === PendingProcurementCodeBudgetRequestNotification::class) {
            return $user->hasActiveRole('Budget Officer');
        }

        if ($notification->type === ProcurementPlanForReviewNotification::class) {
            $targetRoles = data_get($notification->data, 'target_roles', ['Budget Officer']);

            return $user->hasActiveRole($targetRoles);
        }

        if ($notification->type === ProcurementCommentMentioned::class) {
            return in_array(data_get($notification->data, 'reason', 'mention'), ['mention', 'owner'], true);
        }

        if ($notification->type === ProcurementPlanCommentMentioned::class) {
            return in_array(data_get($notification->data, 'reason', 'mention'), ['mention', 'owner'], true);
        }

        return false;
    }

    protected function transformProcurementNotification($notification): ?array
    {
        $actor = data_get($notification->data, 'actor')
            ?: data_get($notification->data, 'mentioned_by');

        if ($notification->type === PendingSupplierApprovalNotification::class) {
            $supplierId = data_get($notification->data, 'supplier.id');

            return [
                'id' => $notification->id,
                'notification_type' => 'supplier_pending_approval',
                'reason' => data_get($notification->data, 'reason', 'approval_required'),
                'supplier_id' => $supplierId,
                'procurement_id' => null,
                'procurement_code' => data_get($notification->data, 'supplier.code'),
                'procurement_purpose' => data_get($notification->data, 'supplier.name'),
                'comment_id' => null,
                'comment_content' => data_get($notification->data, 'message'),
                'actor' => $actor,
                'mentioned_by' => $actor,
                'created_at' => $notification->created_at,
                'created_ago' => $notification->created_at?->diffForHumans(),
                'context_label' => 'Supplier Approval',
                'action_label' => 'Review supplier',
                'target' => [
                    'route' => '/faims/suppliers',
                    'query' => array_filter([
                        'status' => 'pending_approval',
                        'supplier_id' => $supplierId,
                    ]),
                ],
            ];
        }

        if ($notification->type === PendingProcurementCodeBudgetRequestNotification::class) {
            $budgetRequestId = data_get($notification->data, 'budget_request.id');

            return [
                'id' => $notification->id,
                'notification_type' => 'procurement_code_budget_request',
                'reason' => data_get($notification->data, 'reason', 'budget_review_required'),
                'supplier_id' => null,
                'procurement_id' => data_get($notification->data, 'procurement_code.id'),
                'procurement_code' => data_get($notification->data, 'procurement_code.code'),
                'procurement_purpose' => data_get($notification->data, 'procurement_code.title'),
                'comment_id' => null,
                'comment_content' => data_get($notification->data, 'message'),
                'actor' => $actor,
                'mentioned_by' => $actor,
                'created_at' => $notification->created_at,
                'created_ago' => $notification->created_at?->diffForHumans(),
                'context_label' => 'Budget Review',
                'action_label' => 'Review request',
                'target' => [
                    'route' => '/faims/procurement-code-budget-requests',
                    'query' => array_filter([
                        'status' => 'pending',
                        'budget_request_id' => $budgetRequestId,
                    ]),
                ],
            ];
        }

        if ($notification->type === ProcurementPlanForReviewNotification::class) {
            $planId = data_get($notification->data, 'procurement_plan.id');
            $planType = $this->normalizePlanType(data_get($notification->data, 'procurement_plan.plan_type'));
            $targetRole = data_get($notification->data, 'procurement_plan.target_role')
                ?: collect(data_get($notification->data, 'target_roles', ['Budget Officer']))->first();
            $targetStatus = $targetRole === 'Procurement Officer'
                ? 'Reviewed/For Submission'
                : 'For Review';

            return [
                'id' => $notification->id,
                'notification_type' => 'procurement_plan_for_review',
                'reason' => data_get($notification->data, 'reason', 'plan_review_required'),
                'supplier_id' => null,
                'procurement_id' => $planId,
                'procurement_code' => data_get($notification->data, 'procurement_plan.code')
                    ?: data_get($notification->data, 'procurement_plan.ppmp_no'),
                'procurement_purpose' => data_get($notification->data, 'procurement_plan.purpose')
                    ?: data_get($notification->data, 'procurement_plan.title')
                    ?: data_get($notification->data, 'message'),
                'comment_id' => null,
                'comment_content' => data_get($notification->data, 'message'),
                'actor' => $actor,
                'mentioned_by' => $actor,
                'created_at' => $notification->created_at,
                'created_ago' => $notification->created_at?->diffForHumans(),
                'context_label' => "{$planType} {$targetStatus}",
                'action_label' => $targetRole === 'Procurement Officer' ? 'Submit plan' : 'Review plan',
                'target' => [
                    'route' => "/faims/procurement-ppmp/{$planId}",
                    'query' => array_filter([
                        'option' => 'view',
                        'plan_type' => $planType,
                        'status' => $targetStatus,
                        'plan_id' => $planId,
                    ]),
                ],
            ];
        }

        if ($notification->type === ProcurementPlanCommentMentioned::class) {
            $planId = data_get($notification->data, 'procurement_plan.id');
            $planType = $this->normalizePlanType(data_get($notification->data, 'procurement_plan.plan_type'));
            $reason = data_get($notification->data, 'reason', 'mention');

            return [
                'id' => $notification->id,
                'notification_type' => data_get($notification->data, 'type', 'procurement_plan_comment_notification'),
                'reason' => $reason,
                'procurement_id' => $planId,
                'procurement_code' => data_get($notification->data, 'procurement_plan.ppmp_no')
                    ?: data_get($notification->data, 'procurement_plan.code'),
                'procurement_purpose' => data_get($notification->data, 'procurement_plan.purpose')
                    ?: data_get($notification->data, 'procurement_plan.title'),
                'comment_id' => data_get($notification->data, 'comment.id'),
                'comment_content' => data_get($notification->data, 'comment.content'),
                'actor' => $actor,
                'mentioned_by' => $actor,
                'created_at' => $notification->created_at,
                'created_ago' => $notification->created_at?->diffForHumans(),
                'context_label' => $reason === 'owner' ? 'Your Plan' : 'Plan Mention',
                'action_label' => 'Open plan chat',
                'target' => [
                    'route' => "/faims/procurement-ppmp/{$planId}",
                    'query' => array_filter([
                        'option' => 'view',
                        'open_chat' => 1,
                        'comment_id' => data_get($notification->data, 'comment.id'),
                        'plan_type' => $planType,
                    ]),
                ],
            ];
        }

        $procurementId = data_get($notification->data, 'procurement.id');
        $reason = data_get($notification->data, 'reason', 'mention');

        return [
            'id' => $notification->id,
            'notification_type' => data_get($notification->data, 'type', 'procurement_comment_notification'),
            'reason' => $reason,
            'procurement_id' => $procurementId,
            'procurement_code' => data_get($notification->data, 'procurement.code'),
            'procurement_purpose' => data_get($notification->data, 'procurement.purpose'),
            'comment_id' => data_get($notification->data, 'comment.id'),
            'comment_content' => data_get($notification->data, 'comment.content'),
            'actor' => $actor,
            'mentioned_by' => $actor,
            'created_at' => $notification->created_at,
            'created_ago' => $notification->created_at?->diffForHumans(),
            'context_label' => $reason === 'owner' ? 'Your PR' : 'Mentioned You',
            'action_label' => 'Open PR chat',
            'target' => [
                'route' => '/faims/procurements',
                'query' => [
                    'comment_request_id' => $procurementId,
                ],
            ],
        ];
    }

    protected function normalizePlanType(?string $planType): ?string
    {
        return match ($planType) {
            'APP', 'annual', 'Annual Procurement Plan' => 'APP',
            'SPP', 'supplemental', 'Supplemental Procurement Plan' => 'SPP',
            'PPMP', 'ppmp', 'Project Procurement Management Plan' => 'PPMP',
            default => $planType,
        };
    }
}
