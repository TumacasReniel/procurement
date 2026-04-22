<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Events\CommentAdded;
use App\Http\Controllers\Controller;
use App\Models\OrgChart;
use App\Models\OrgSignatory;
use App\Models\Procurement;
use App\Models\ProcurementCode;
use App\Models\RequestComment;
use App\Models\User;
use App\Notifications\ProcurementCommentMentioned;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use App\Models\ListDropdown;
use App\Services\DropdownClass;
use App\Services\Executive\Users\SaveClass;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\FAIMS\Procurement\ProcurementClass;
use App\Services\FAIMS\Procurement\ViewClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProcurementController extends Controller
{
     use HandlesTransaction;

    public $dropdown, $view, $procurement , $user , $print;

    public function __construct(
        DropdownClass $dropdown,
        ViewClass $view, 
        PrintClass $print, 
        ProcurementClass $procurement,
        SaveClass $user,
    ){
        $this->dropdown = $dropdown;
        $this->view = $view;
        $this->print = $print;
        $this->procurement = $procurement;
        $this->user = $user;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                return $this->view->procurements($request);
            break;

            case 'chat_lists':
                return $this->view->chatProcurements($request);
            break;

            case 'quotations':
                return $this->view->quotations($request);
            break;

            case 'dashboard':
                return $this->view->dashboard($request);
            break;

            default:
                $regionalDirector = $this->dropdown->regional_director();
                $procurementApprovalUserIds = OrgSignatory::query()
                    ->where(function ($query) {
                        $query->where('user_id', \Auth::id())
                            ->orWhere('oic_id', \Auth::id());
                    })
                    ->where('is_active', 1)
                    ->pluck('user_id')
                    ->push(\Auth::id())
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return inertia('Modules/FAIMS/Procurement/Index', [
                    'dropdowns' => [
                        'roles'  =>  \Auth::user()->roles,
                        'designation'  =>  \Auth::user()->org_chart?->designation,
                        'statuses' => $this->dropdown->statuses('Procurement'),
                        'types' => $this->dropdown->dropdowns('Type'),
                        'modes' => $this->dropdown->dropdowns('Mode'),
                    ],
                    'regional_director'  =>  $regionalDirector,
                    'is_regional_director' => $regionalDirector && $regionalDirector['value'] == \Auth::id(),
                    'procurement_approval_user_ids' => $procurementApprovalUserIds,
                    'chat_request_id' => $request->integer('chat_request_id') ?: null,
                ]);
        }
    }

    public function create_index(Request $request){
        
        switch($request->option){     
            case 'units':
               return  $this->dropdown->units($request->code);
            break;
            case 'unit_type':
                return $this->dropdown->unit_type($request->code);
            break;
            case 'title':
                return $this->procurement->procurement_title($request->id);
            break;
            case 'item_names':
                return $this->procurement->item_names($request->keyword);
            break;
            default:
                $division_head = null;
                if (\Auth::user()->organization && \Auth::user()->organization->division_id) {
                    $division_head = $this->dropdown->division_head(\Auth::user()->organization->division_id);
                }

                return inertia('Modules/FAIMS/Procurement/CreatePage', [
                    'dropdowns' => [
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'classifications' => $this->dropdown->dropdowns('Classification'),
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),
                        'regional_director' => $this->dropdown->regional_director(),
                        'division_head' => $division_head,
                    ],
                    'option' => $request->option,
                ]);
            break;
        } 
    }

    public function store(Request $request) {
        $this->validateProcurementBudgetAvailability($request);

        $result = $this->handleTransaction(function () use ($request) {
            return $this->procurement->save($request);
        });

        return redirect()->route('procurement.index')->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }



     public function update($id, Request $request) {
        if (in_array($request->option, ['edit', 'review', 'approve'], true)) {
            $this->validateProcurementBudgetAvailability($request);
        }

        $result = $this->handleTransaction(function () use ($id, $request) {
            switch($request->option){     
                case 'edit':
                    return $this->procurement->update($id, $request);
                break;
                case 'review':
                    return $this->procurement->review($id, $request);
                break;
                case 'approve':
                    return $this->procurement->approve($id, $request);
                break;
                case 'cancel':
                    return $this->procurement->cancel($id, $request);
                break;
            }   
           
        });

        return redirect()->route('procurement.index')->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

    public function dashboard(Request $request){
        return inertia('Modules/FAIMS/Procurement/Dashboard', [
            'dropdowns' => [
                'roles'  =>  \Auth::user()->roles,
                'designation'  =>  \Auth::user()->designation,
            ],
        ]);
    }

    public function reports(Request $request){
        if ($request->option === 'print') {
            return $this->print->printReport($request);
        }

        $signatories = $this->reportSignatories();

        return inertia('Modules/FAIMS/Procurement/Reports/Index', [
            'dropdowns' => [
                'roles'  =>  \Auth::user()->roles,
                'designation'  =>  \Auth::user()->org_chart?->designation,
                'statuses' => $this->dropdown->statuses('Procurement'),
                'types' => $this->dropdown->dropdowns('Type'),
                'modes' => $this->dropdown->dropdowns('Mode of Procurement'),
            ],
            'signatories' => $signatories,
        ]);
    }

    private function validateProcurementBudgetAvailability(Request $request): void
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

    private function reportSignatories(): array
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

    public function show($id, Request $request){
        if($request->type){
            return $this->print->print($id, $request);
        }
        if ($request->option === 'comments') {
            return $this->view->commentThread($id);
        }
        else{
            return $this->view->show($id, $request);
        }

    }

    public function addComment($id, Request $request) {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $result = $this->handleTransaction(function () use ($id, $request) {
            $procurement = Procurement::findOrFail($id);

            $comment = $procurement->comments()->create([
                'content' => $request->content,
                'user_id' => auth()->id(),
            ]);

            $comment->load('user.profile');

            $this->notifyCommentRecipients($procurement, $comment);

            // Broadcast the comment to other users
            broadcast(new CommentAdded($comment))->toOthers();

            return [
                'data' => $comment->load('user.profile'),
                'message' => 'Comment added successfully',
                'info' => 'Your comment has been added to the procurement.',
                'status' => true,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $result['data'],
                'status' => $result['status'],
                'message' => $result['message'],
                'info' => $result['info'],
            ]);
        }

        return back()->with([
            'data' => $result['data'],
            'status' => $result['status'],
        ]);


    }

    public function mentionNotifications(Request $request)
    {
        if (!Schema::hasTable('notifications')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'unread_count' => 0,
                    'has_more' => false,
                ],
            ]);
        }

        if (!$request->user()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'unread_count' => 0,
                    'has_more' => false,
                ],
            ], 401);
        }

        $limit = max(1, min((int) $request->input('limit', 4), 10));

        $query = $request->user()
            ->unreadNotifications()
            ->where('type', ProcurementCommentMentioned::class)
            ->latest();

        $unreadCount = (clone $query)->count();

        $notifications = (clone $query)
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                $procurementId = data_get($notification->data, 'procurement.id');
                $reason = data_get($notification->data, 'reason', 'mention');
                $actor = data_get($notification->data, 'actor')
                    ?: data_get($notification->data, 'mentioned_by');

                return [
                    'id' => $notification->id,
                    'reason' => $reason,
                    'procurement_id' => data_get($notification->data, 'procurement.id'),
                    'procurement_code' => data_get($notification->data, 'procurement.code'),
                    'procurement_purpose' => data_get($notification->data, 'procurement.purpose'),
                    'comment_id' => data_get($notification->data, 'comment.id'),
                    'comment_content' => data_get($notification->data, 'comment.content'),
                    'actor' => $actor,
                    'mentioned_by' => $actor,
                    'created_at' => $notification->created_at,
                    'created_ago' => $notification->created_at?->diffForHumans(),
                    'context_label' => $reason === 'owner' ? 'Your PR' : 'Mentioned You',
                    'target' => [
                        'route' => '/faims/procurements',
                        'query' => [
                            'chat_request_id' => $procurementId,
                        ],
                    ],
                ];
            })
            ->values();

        return response()->json([
            'data' => $notifications,
            'meta' => [
                'unread_count' => $unreadCount,
                'has_more' => $unreadCount > $notifications->count(),
            ],
        ]);
    }

    public function markMentionNotificationRead(string $notificationId, Request $request)
    {
        if (!Schema::hasTable('notifications')) {
            return response()->json([
                'status' => false,
            ]);
        }

        if (!$request->user()) {
            return response()->json([
                'status' => false,
            ], 401);
        }

        $notification = $request->user()
            ->notifications()
            ->where('type', ProcurementCommentMentioned::class)
            ->findOrFail($notificationId);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => true,
        ]);
    }

    private function notifyCommentRecipients(Procurement $procurement, RequestComment $comment): void
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

    private function resolveCommentNotificationRecipients(
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

    private function extractMentionedUsernames(string $content): Collection
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $content, $matches);

        return collect($matches[1] ?? [])
            ->map(fn ($username) => strtolower((string) $username))
            ->filter()
            ->unique()
            ->values();
    }

    private function findMentionedUsers(Collection $usernames, int $excludedUserId): Collection
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


}
