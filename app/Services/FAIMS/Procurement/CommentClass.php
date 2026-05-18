<?php

namespace App\Services\FAIMS\Procurement;

use App\Events\CommentAdded;
use App\Models\Procurement;
use App\Models\RequestComment;
use App\Models\User;
use App\Notifications\ProcurementCommentMentioned;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CommentClass
{
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
}
