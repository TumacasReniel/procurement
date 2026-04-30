<?php

namespace App\Services\FAIMS\Procurement;

use App\Events\CommentAdded;
use App\Models\Request;
use App\Models\OrgChart;
use App\Models\OrgSignatory;
use App\Models\Procurement;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeGroup;
use App\Models\ProcurementCodeBudgetLog;
use App\Models\ProcurementItem;
use App\Models\InventoryItem;
use App\Models\RequestComment;
use App\Http\Resources\FAIMS\Procurement\ProcurementResource;
use App\Models\ListDropdown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\ListStatus;
use App\Models\ListData;
use App\Notifications\PendingProcurementCodeBudgetRequestNotification;
use App\Notifications\PendingSupplierApprovalNotification;
use App\Notifications\ProcurementCommentMentioned;
use App\Services\DropdownClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProcurementClass
{
    public function __construct(protected DropdownClass $dropdown)
    {
    }

    public function indexPageProps($request): array
    {
        $regionalDirector = $this->dropdown->regional_director();

        return [
            'dropdowns' => [
                'roles'  =>  Auth::user()->roles,
                'designation'  =>  Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'types' => $this->dropdown->dropdowns('Type'),
                'modes' => $this->dropdown->dropdowns('Mode'),
            ],
            'regional_director'  =>  $regionalDirector,
            'is_regional_director' => $regionalDirector && $regionalDirector['value'] == Auth::id(),
            'procurement_approval_user_ids' => $this->procurementApprovalUserIds(),
            'comment_request_id' => $request->integer('comment_request_id')
                ?: $request->integer('chat_request_id')
                ?: null,
        ];
    }

    public function createPageProps($request): array
    {
        $divisionHead = null;
        if (Auth::user()->organization && Auth::user()->organization->division_id) {
            $divisionHead = $this->dropdown->division_head(Auth::user()->organization->division_id);
        }

        return [
            'dropdowns' => [
                'divisions' => $this->dropdown->dropdowns('Division'),
                'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                'classifications' => $this->dropdown->dropdowns('Classification'),
                'reference_apps' => $this->dropdown->dropdowns('Reference APP'),
                'procurement_codes' => $this->dropdown->procurement_codes(),
                'unit_types' => $this->dropdown->unit_types(),
                'requesters' => $this->dropdown->requesters(),
                'approvers' => $this->dropdown->approvers(),
                'regional_director' => $this->dropdown->regional_director(),
                'division_head' => $divisionHead,
            ],
            'option' => $request->option,
        ];
    }

    public function createIndexData($request)
    {
        return match ($request->option) {
            'units' => $this->dropdown->units($request->code),
            'unit_type' => $this->dropdown->unit_type($request->code),
            'title' => $this->procurement_title($request->id),
            'item_names' => $this->item_names($request->keyword),
            default => null,
        };
    }

    public function dashboardPageProps(): array
    {
        return [
            'dropdowns' => [
                'roles'  =>  Auth::user()->roles,
                'designation'  =>  Auth::user()->designation,
            ],
        ];
    }

    public function reportPageProps(): array
    {
        return [
            'dropdowns' => [
                'roles'  =>  Auth::user()->roles,
                'designation'  =>  Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'types' => $this->dropdown->dropdowns('Type'),
                'modes' => $this->dropdown->dropdowns('mode_of_procurement'),
            ],
            'signatories' => $this->reportSignatories(),
        ];
    }

    protected function procurementApprovalUserIds(): array
    {
        return OrgSignatory::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('oic_id', Auth::id());
            })
            ->where('is_active', 1)
            ->pluck('user_id')
            ->push(Auth::id())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function validateProcurementBudgetAvailability($request): void
    {
        $validator = Validator::make(
            $request->all(),
            [
                'procurement_code_ids' => ['nullable', 'array'],
                'procurement_code_ids.*' => ['integer', 'distinct', 'exists:procurement_codes,id'],
                'items' => ['nullable', 'array'],
                'items.*.total_cost' => ['nullable', 'numeric', 'min:0'],
            ],
            [
                'procurement_code_ids.*.exists' => 'One or more selected PAP codes are no longer available.',
            ]
        );

        $validator->after(function ($validator) use ($request) {
            $procurementCodeIds = collect($request->input('procurement_code_ids', []))
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($procurementCodeIds->isEmpty()) {
                return;
            }

            $requestedAmount = collect($request->input('items', []))
                ->sum(fn ($item) => (float) data_get($item, 'total_cost', 0));

            if ($requestedAmount <= 0) {
                return;
            }

            $availableAmount = ProcurementCode::query()
                ->whereIn('id', $procurementCodeIds)
                ->get(['remaining_budget', 'allocated_budget'])
                ->sum(function ($code) {
                    return (float) ($code->remaining_budget ?? $code->allocated_budget ?? 0);
                });

            if (($availableAmount + 0.009) >= $requestedAmount) {
                return;
            }

            $validator->errors()->add(
                'procurement_code_ids',
                sprintf(
                    'The selected PAP codes only have PHP %s remaining, which is not enough for the request total of PHP %s.',
                    number_format($availableAmount, 2),
                    number_format($requestedAmount, 2)
                )
            );
        });

        $validator->validate();
    }

    public function updateByOption($id, $request): array
    {
        return match ($request->option) {
            'edit' => $this->update($id, $request),
            'review' => $this->review($id, $request),
            'approve' => $this->approve($id, $request),
            'cancel' => $this->cancel($id, $request),
            default => [
                'data' => null,
                'message' => 'Invalid procurement action.',
                'info' => 'The requested procurement action is not supported.',
                'status' => false,
            ],
        };
    }

    public function save($request){
        $data = Request::create([
            'code' => $this->generateCode(),
            'type_id' => ListData::getID('Procurement'),
            'status_id' => ListStatus::getID('Pending','Procurement'),
            'user_id' => \Auth::user()->id
        ]);
                                         
        // Save Procurement
        $procurement = $this->saveProcurement($request, $data);

        // Save Procurement Items 
        $this->saveProcurementItems($request, $procurement->id);

        return [
            'data' => new ProcurementResource($procurement),
            'message' => 'Procurement creation was successful!', 
            'info' => "You've successfully created new Procurement.",
        ];
    }

    public function saveProcurement($request, $data){
        $user = Auth::user();
        $purchase_request_number = Procurement::generateProcurementNumber();
        $payload = array_merge($request->all(), [
            'code' => $purchase_request_number,
            'status_id' => ListStatus::getID('Pending', 'Procurement'), //set to "Pending"
            'created_by_id' => $user->id,
        ]);

        // Handle schema drift safely for older DBs that may not yet have request_id.
        if (Schema::hasColumn('procurements', 'request_id')) {
            $payload['request_id'] = $data->id;
        }

        $procurement = Procurement::create($payload);

        if (!empty($request->procurement_code_ids) && is_array($request->procurement_code_ids)) {
            $this->syncProcurementCodes($procurement->id, $request->procurement_code_ids);
        }

      
        return $procurement;
    }
    

    protected function saveProcurementItems($request ,$procurement_id ){
    
        foreach ($request->items as $index => $item) {
            $data = new ProcurementItem();
            $data->item_no = $index + 1;
            $data->procurement_id = $procurement_id;
            $data->item_unit_type_id =  $item['item_unit_type_id'];
            $data->item_name = $item['item_name'] ?? null;
            $data->item_unit_cost = $item['item_unit_cost'];
            $data->item_quantity = $item['item_quantity'];
            $data->item_description = $item['item_description'];
            $data->total_cost = $item['total_cost'];
            $data->status_id = ListStatus::getID('Pending','Procurement');
            $data->save();
        }

    }

    
    private function generateCode()
    {
        return \DB::transaction(function () {
            $latest = Request::lockForUpdate()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->orderByDesc('id')
                ->first();

            $count = $latest
                ? (int) substr($latest->code, -4) + 1
                : 1;

            $code = 'REQUEST-' . now()->format('mY') . '-PR-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            return $code;
        });
    }
    
    public function update($id , $request)
    {
        // update Procurement
        $data = $this->updatePR($id , $request);

        // update Procurement PAP Codes
        $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

        // update Procurement Item Details
        $this->updatePRItems($id , $request);


        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement updated successfuly!',
            'info' => "You've successfully updated the Procurement.",
        ];
    }
    
   
    public function review($id, $request)
    {
        $user = Auth::user();
        Log::info('Procurement review started', [
            'procurement_id' => $id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'review_procurement'
        ]);

        try {
            if (!$request->filled('classification_id')) {
                $procurement = Procurement::findOrFail($id);

                return [
                    'data' => new ProcurementResource($procurement),
                    'message' => 'Procurement classification is required.',
                    'info' => 'Please select whether this PR is for Goods and Services, Infrastructure Projects, or Consulting Services before reviewing.',
                    'status' => 'warning',
                ];
            }

            // update Procurement
            $data = $this->updatePR($id , $request);

            // update Procurement PAP Codes
            $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

            // update Procurement Item Details
            $this->updatePRItems($id, $request);

            //  update status to reviewed
            $data->status_id  = ListStatus::getID('Reviewed','Procurement');

            $data->update();

            Log::info('Procurement reviewed successfully', [
                'procurement_id' => $id,
                'procurement_code' => $data->code,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'new_status_id' => ListStatus::getID('Reviewed','Procurement')
            ]);

            return [
                'data' => new ProcurementResource($data),
                'message' => 'Procurement reviewed successfuly!',
                'info' => "You've successfully updated the Procurement.",
            ];
        } catch (\Exception $e) {
            Log::error('Procurement review failed', [
                'procurement_id' => $id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function approve($id, $request)
    {
        // update Procurement
        $data = $this->updatePR($id , $request);

        // update Procurement PAP Codes
        $this->syncProcurementCodes($id, $request->procurement_code_ids ?? []);

        // update Procurement Item Details       
        $this->updatePRItems($id, $request);

        $this->applyApprovedBudgetDeductions($id);

        //  update status to approved
        $data->status_id  = ListStatus::getID('Approved','Procurement');

        $data->update();

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement reviewed successfuly!', 
            'info' => "You've successfully updated the Procurement.",
        ];
    }


    public function cancel($id, $request)
    {
        $data = Procurement::with('request')->findOrFail($id);
        $user = Auth::user();

        if ((int) $data->created_by_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'code' => 'Only the creator of this purchase request can cancel it.',
            ]);
        }

        if ($data->status?->name !== null && $data->status?->name !== 'Pending') {
            throw ValidationException::withMessages([
                'code' => 'Only pending purchase requests can be cancelled.',
            ]);
        }

        $cancelledStatusId = ListStatus::getID('Cancelled', 'Procurement');

        if (!$cancelledStatusId) {
            throw ValidationException::withMessages([
                'code' => 'The cancelled procurement status is not configured.',
            ]);
        }

        $data->status_id = $cancelledStatusId;
        $data->save();

        if ($data->request) {
            $data->request->is_completed = 1;
            $data->request->save();
        }

        $data->refresh();

        return [
            'data' => new ProcurementResource($data),
            'message' => 'Procurement cancelled successfully!',
            'info' => "You've successfully cancelled the purchase request.",
        ];
    }

    public function destroy($id): array
    {
        $procurement = Procurement::findOrFail($id);
        $procurement->delete();

        return [
            'data' => $id,
            'message' => 'Procurement deleted successfully!',
            'info' => "You've successfully deleted the Procurement.",
            'status' => true,
        ];
    }
    
       
    protected function updatePR($id, $request ){
        $data = Procurement::findOrFail($id);

        $data->update(array_merge($request->only(
            'date',
            'purpose',
            'title',
            'division_id',
            'unit_id',
            'fund_cluster_id',
            'classification_id',
            'reference_app_id',
            'requested_by_id',
            'approved_by_id'
        )));

        return  $data;
    }

    protected function syncProcurementCodes($procurement_id, $procurementCodeIds = []): void
    {
        ProcurementCodeGroup::where('procurement_id', $procurement_id)->delete();

        foreach (collect($procurementCodeIds)->filter()->unique() as $procurement_code_id) {
            ProcurementCodeGroup::create([
                'procurement_code_id' => $procurement_code_id,
                'procurement_id' => $procurement_id,
            ]);
        }
    }

    protected function updatePRItems($procurement_id, $request ){

        // Delete existing items for the procurement
        ProcurementItem::where('procurement_id', $procurement_id)->delete();

        // Re-save the updated items
        $this->saveProcurementItems($request, $procurement_id);
    }

    protected function applyApprovedBudgetDeductions(int $procurementId): void
    {
        $existingLogs = ProcurementCodeBudgetLog::query()
            ->where('procurement_id', $procurementId)
            ->where('type', 'approval_deduction')
            ->get();

        $procurement = Procurement::with(['codes'])
            ->findOrFail($procurementId);

        $procurementCodeIds = $procurement->codes
            ->pluck('procurement_code_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($procurementCodeIds)) {
            return;
        }

        $remainingDeductionCents = $this->amountToCents(
            ProcurementItem::where('procurement_id', $procurementId)->sum('total_cost')
        );

        $alreadyDeductedCents = $this->amountToCents($existingLogs->sum('amount'));
        $remainingDeductionCents -= $alreadyDeductedCents;

        if ($remainingDeductionCents <= 0) {
            return;
        }

        $alreadyLoggedCodeIds = $existingLogs
            ->pluck('procurement_code_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $budgetCodes = ProcurementCode::query()
            ->whereIn('id', $procurementCodeIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lastIndex = count($procurementCodeIds) - 1;

        foreach ($procurementCodeIds as $index => $procurementCodeId) {
            if ($remainingDeductionCents <= 0) {
                break;
            }

            $budgetCode = $budgetCodes->get($procurementCodeId);

            if (!$budgetCode) {
                continue;
            }

            if (in_array($procurementCodeId, $alreadyLoggedCodeIds, true)) {
                continue;
            }

            $balanceBeforeCents = $this->amountToCents(
                $budgetCode->remaining_budget ?? $budgetCode->allocated_budget
            );

            $amountCents = $index === $lastIndex
                ? $remainingDeductionCents
                : min($remainingDeductionCents, max($balanceBeforeCents, 0));

            if ($amountCents <= 0 && $index !== $lastIndex) {
                continue;
            }

            $balanceAfterCents = $balanceBeforeCents - $amountCents;

            ProcurementCodeBudgetLog::create([
                'procurement_code_id' => $budgetCode->id,
                'procurement_id' => $procurementId,
                'processed_by_id' => Auth::id(),
                'type' => 'approval_deduction',
                'amount' => $this->centsToAmount($amountCents),
                'balance_before' => $this->centsToAmount($balanceBeforeCents),
                'balance_after' => $this->centsToAmount($balanceAfterCents),
                'description' => 'Budget deducted after approving procurement request ' . $procurement->code,
            ]);

            $budgetCode->remaining_budget = $this->centsToAmount($balanceAfterCents);
            $budgetCode->save();

            $remainingDeductionCents -= $amountCents;
        }
    }

    protected function amountToCents($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    protected function centsToAmount(int $amountInCents): float
    {
        return round($amountInCents / 100, 2);
    }

    

    public function procurement_title($code_id)
    {  
        $data = ProcurementCode::findOrFail($code_id);
        return $data->title;
    }

    public function item_names($keyword = null)
    {
        $keyword = trim((string) $keyword);
        $limit = 20;
        $names = collect();

        if (Schema::hasTable('procurement_items')) {
            $names = $names->merge(
                ProcurementItem::query()
                    ->select('item_name')
                    ->whereNotNull('item_name')
                    ->where('item_name', '!=', '')
                    ->when($keyword !== '', function ($query) use ($keyword) {
                        $query->where('item_name', 'like', '%' . $keyword . '%');
                    })
                    ->distinct()
                    ->orderBy('item_name')
                    ->limit($limit)
                    ->pluck('item_name')
            );
        }

        if (Schema::hasTable('inventory_items')) {
            $names = $names->merge(
                InventoryItem::query()
                    ->select('name')
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->when($keyword !== '', function ($query) use ($keyword) {
                        $query->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->distinct()
                    ->orderBy('name')
                    ->limit($limit)
                    ->pluck('name')
            );
        }

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->sortBy(fn ($name) => mb_strtolower($name))
            ->values()
            ->take($limit)
            ->all();
    }

    protected function reportSignatories(): array
    {
        $procurementStaff = User::with('profile')
            ->whereHas('roles', function ($query) {
                $query->where('list_roles.name', 'Procurement Staff');
            })
            ->get()
            ->map(function ($user) {
                return [
                    'name' => strtoupper($user->profile?->full_name ?? ('USER #' . $user->id)),
                    'role' => 'Procurement Staff',
                ];
            })
            ->values()
            ->all();

        $supplyOfficer = User::with('profile')
            ->whereHas('roles', function ($query) {
                $query->where('list_roles.name', 'Supply Officer');
            })
            ->first();

        $assistantRegionalDirector = OrgChart::with('user.profile', 'designation')
            ->where('designation_id', ListDropdown::getID('Assistant Regional Director', 'Designation'))
            ->first();

        return [
            'prepared_by' => array_slice($procurementStaff, 0, 2),
            'supply_officer' => $supplyOfficer ? [
                'name' => strtoupper($supplyOfficer->profile?->full_name ?? ('USER #' . $supplyOfficer->id)),
                'role' => 'Supply Officer',
            ] : null,
            'noted_by' => $assistantRegionalDirector ? [
                'name' => strtoupper($assistantRegionalDirector->user?->profile?->full_name ?? ''),
                'designation' => 'ARD-FASS',
            ] : null,
        ];
    }

    public function addComment($id, $request): array
    {
        $procurement = Procurement::findOrFail($id);

        $comment = $procurement->comments()->create([
            'content' => $request->content,
            'user_id' => Auth::id(),
        ]);

        $comment->load('user.profile');

        $this->notifyCommentRecipients($procurement, $comment);

        broadcast(new CommentAdded($comment))->toOthers();

        return [
            'data' => $comment->load('user.profile'),
            'message' => 'Comment added successfully',
            'info' => 'Your comment has been added to the procurement.',
            'status' => true,
        ];
    }

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
            ->whereIn('type', [
                ProcurementCommentMentioned::class,
                PendingProcurementCodeBudgetRequestNotification::class,
                PendingSupplierApprovalNotification::class,
            ])
            ->latest();

        $visibleNotifications = (clone $query)
            ->get()
            ->filter(fn ($notification) => $this->procurementNotificationVisibleToUser($notification, $request->user()))
            ->values();

        $unreadCount = $visibleNotifications->count();

        $notifications = $visibleNotifications
            ->take($limit)
            ->map(function ($notification) {
                return $this->transformProcurementNotification($notification);
            })
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
            ->whereIn('type', [
                ProcurementCommentMentioned::class,
                PendingProcurementCodeBudgetRequestNotification::class,
                PendingSupplierApprovalNotification::class,
            ])
            ->findOrFail($notificationId);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return ['status' => true];
    }

    protected function procurementNotificationVisibleToUser($notification, User $user): bool
    {
        if ($notification->type === PendingSupplierApprovalNotification::class) {
            return $user->hasRole('Procurement Officer') || $user->hasRole('Administrator');
        }

        if ($notification->type === PendingProcurementCodeBudgetRequestNotification::class) {
            return $user->hasRole('Budget Officer');
        }

        if ($notification->type === ProcurementCommentMentioned::class) {
            return in_array(data_get($notification->data, 'reason', 'mention'), ['mention', 'owner'], true);
        }

        return false;
    }

    protected function notifyCommentRecipients(Procurement $procurement, RequestComment $comment): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $author = $comment->relationLoaded('user')
            ? $comment->user
            : User::with('profile')->find($comment->user_id);

        if (!$author) {
            return;
        }

        $recipients = $this->resolveCommentNotificationRecipients($procurement, $comment, $author);

        foreach ($recipients as $recipient) {
            $recipient['user']->notify(
                new ProcurementCommentMentioned(
                    $procurement,
                    $comment,
                    $author,
                    $recipient['reason'],
                )
            );
        }
    }

    protected function resolveCommentNotificationRecipients(
        Procurement $procurement,
        RequestComment $comment,
        User $author
    ): Collection {
        $recipients = collect();
        $mentionedUsernames = $this->extractMentionedUsernames((string) $comment->content);

        if ($procurement->created_by_id && (int) $procurement->created_by_id !== (int) $author->id) {
            $owner = User::with('profile')->find($procurement->created_by_id);

            if ($owner) {
                $recipients->push([
                    'user' => $owner,
                    'reason' => 'owner',
                ]);
            }
        }

        $mentionedUsers = $this->findMentionedUsers($mentionedUsernames, (int) $author->id);

        foreach ($mentionedUsers as $mentionedUser) {
            $recipients->push([
                'user' => $mentionedUser,
                'reason' => 'mention',
            ]);
        }

        return $recipients
            ->filter(fn ($recipient) => isset($recipient['user']) && $recipient['user'] instanceof User)
            ->groupBy(fn ($recipient) => (int) $recipient['user']->id)
            ->map(function (Collection $group) {
                $selected = $group
                    ->sortByDesc(fn ($recipient) => $recipient['reason'] === 'mention' ? 2 : 1)
                    ->first();

                return [
                    'user' => $selected['user'],
                    'reason' => $selected['reason'],
                ];
            })
            ->values();
    }

    protected function extractMentionedUsernames(string $content): Collection
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $content, $matches);

        return collect($matches[1] ?? [])
            ->map(fn ($username) => strtolower((string) $username))
            ->filter()
            ->unique()
            ->values();
    }

    protected function findMentionedUsers(Collection $usernames, int $excludedUserId): Collection
    {
        if ($usernames->isEmpty()) {
            return collect();
        }

        return User::query()
            ->with('profile')
            ->where('id', '!=', $excludedUserId)
            ->where(function ($query) use ($usernames) {
                foreach ($usernames as $username) {
                    $query->orWhereRaw('LOWER(username) = ?', [$username]);
                }
            })
            ->get()
            ->unique('id')
            ->values();
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

    
  
   
}
