<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Events\CommentAdded;
use App\Events\ProcurementPlanStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcurementPPMPListRequest;
use App\Http\Requests\Procurement\ProcurementPPMPPlanRequest;
use App\Http\Requests\Procurement\ProcurementPPMPUpdateRequest;
use App\Models\ProcurementApp;
use App\Models\ProcurementPpmp;
use App\Models\User;
use App\Notifications\ProcurementPlanCommentMentioned;
use App\Services\FAIMS\Procurement\PrintClass;
use App\Services\FAIMS\Procurement\ProcurementPPMPClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProcurementPPMPController extends Controller
{
    use HandlesTransaction;

    public $ppmp, $print;

    public function __construct(
        ProcurementPPMPClass $ppmp,
        PrintClass $print,
    ) {
        $this->ppmp = $ppmp;
        $this->print = $print;
    }

    public function index(ProcurementPPMPListRequest $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->ppmp->lists($request);

            case 'available_units':
                return $this->ppmp->availablePpmpUnits($request);

            case 'available_spp_units':
                return $this->ppmp->availableSppUnits($request);

            default:
                return inertia('Modules/FAIMS/Procurement/PPMP/Index', $this->ppmp->indexPageProps());
        }
    }

    public function store(ProcurementPPMPPlanRequest $request)
    {
        $result = $this->handleTransaction(function () use ($request) {
            return $this->ppmp->store($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function update($id, ProcurementPPMPUpdateRequest $request)
    {
        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->ppmp->updateByOption($id, $request);
        });

        switch ($request->option) {
            case 'update_status':
            case 'approve_to_app':
            case 'add_item':
            case 'update_item':
            case 'delete_item':
                broadcast(new ProcurementPlanStatusUpdated([
                    'id' => (int) $id,
                    'plan_type' => $request->input('plan_type', 'PPMP'),
                    'option' => $request->option,
                    'updated_by_id' => auth()->id(),
                    'updated_at' => now()->toDateTimeString(),
                ]))->toOthers();

                break;
        }

        return back()->with($result);
    }

    public function show($id, Request $request)
    {
        if ($request->type) {
            return $this->print->print($id, $request);
        }

        if ($request->option === 'comments') {
            return $this->comments($id);
        }

        return inertia('Modules/FAIMS/Procurement/PPMP/View', $this->ppmp->showPageProps($id, $request));
    }

    public function comments($id)
    {
        if ($this->isAppCommentRequest(request())) {
            $app = ProcurementApp::with([
                'app_type',
                'created_by.profile',
                'requested_by.profile',
                'reviewed_by.profile',
                'approved_by.profile',
                'status',
                'comments.user.profile',
                'comments.replies.user.profile',
            ])
                ->withCount('comments')
                ->findOrFail($id);

            return response()->json([
                'data' => $app,
            ]);
        }

        $ppmp = ProcurementPpmp::with([
            'unit',
            'division',
            'status',
            'created_by.profile',
            'requested_by.profile',
            'approved_by.profile',
            'comments.user.profile',
            'comments.replies.user.profile',
        ])
            ->withCount('comments')
            ->findOrFail($id);

        return response()->json([
            'data' => $ppmp,
        ]);
    }

    public function storeComment($id, Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        if ($this->isAppCommentRequest($request)) {
            $app = ProcurementApp::findOrFail($id);
            $comment = $app->comments()->create([
                'user_id' => auth()->id(),
                'content' => $validated['content'],
            ]);

            $comment->load('user.profile');
            $this->logPlanCommentActivity($app, 'APP comment added', $comment->id);
            $this->notifyMentionedUsers($app, $comment);
            broadcast(new CommentAdded($comment))->toOthers();

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $comment,
                ]);
            }

            return back()->with([
                'data' => $comment,
            ]);
        }

        $ppmp = ProcurementPpmp::findOrFail($id);
        $comment = $ppmp->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        $comment->load('user.profile');
        $this->logPlanCommentActivity($ppmp, 'Procurement plan comment added', $comment->id);
        $this->notifyMentionedUsers($ppmp, $comment);
        broadcast(new CommentAdded($comment))->toOthers();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $comment,
            ]);
        }

        return back()->with([
            'data' => $comment,
        ]);
    }

    protected function logPlanCommentActivity(ProcurementPpmp|ProcurementApp $plan, string $description, int $comment_id): void
    {
        $log_name = $plan instanceof ProcurementApp ? 'APP' : 'Procurement Plan';
        $plan_type = $plan instanceof ProcurementApp
            ? 'APP'
            : match ($plan->reference_app?->name) {
                'Supplemental Procurement Plan' => 'SPP',
                'Annual Procurement Plan' => 'APP',
                default => str_starts_with((string) $plan->code, 'SPP-') ? 'SPP' : 'PPMP',
            };

        $logger = activity($log_name)
            ->performedOn($plan)
            ->withProperties([
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'plan_type' => $plan_type,
                'comment_id' => $comment_id,
            ]);

        if (auth()->user()) {
            $logger->causedBy(auth()->user());
        }

        $logger->log($description);
    }

    protected function notifyMentionedUsers(ProcurementPpmp|ProcurementApp $ppmp, $comment): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $author = $comment->user ?: User::with('profile')->find($comment->user_id);

        if (!$author) {
            return;
        }

        $this->commentNotificationRecipients($ppmp, $comment, $author)
            ->each(fn (array $recipient) => $recipient['user']->notify(
                new ProcurementPlanCommentMentioned($ppmp, $comment, $author, $recipient['reason'])
            ));
    }

    protected function commentNotificationRecipients(ProcurementPpmp|ProcurementApp $ppmp, $comment, User $author): Collection
    {
        $recipients = collect();

        if ($ppmp->created_by_id && (int) $ppmp->created_by_id !== (int) $author->id) {
            $owner = User::with('profile')->find($ppmp->created_by_id);

            if ($owner) {
                $recipients->push([
                    'user' => $owner,
                    'reason' => 'owner',
                ]);
            }
        }

        $this->mentionedUsers((string) $comment->content, (int) $author->id)
            ->each(fn (User $user) => $recipients->push([
                'user' => $user,
                'reason' => 'mention',
            ]));

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

    protected function isAppCommentRequest(Request $request): bool
    {
        return in_array($request->input('plan_type'), ['APP', 'annual'], true);
    }

    protected function mentionedUsers(string $content, int $excludedUserId): Collection
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $content, $matches);

        $usernames = collect($matches[1] ?? [])
            ->map(fn ($username) => strtolower((string) $username))
            ->filter()
            ->unique()
            ->values();

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
