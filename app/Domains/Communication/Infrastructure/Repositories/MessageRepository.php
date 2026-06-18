<?php

declare(strict_types=1);

namespace App\Domains\Communication\Infrastructure\Repositories;

use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Communication\DTOs\MessageData;
use App\Domains\Communication\Events\MessageRead as MessageReadEvent;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\MessageRead;
use App\Domains\Identity\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MessageRepository implements MessageRepositoryInterface
{
    public function create(MessageData $data): object
    {
        $message = Message::create([
            'group_id' => $data->groupId,
            'sender_id' => $data->senderId,
            'message_text' => $data->content->getValue(),
        ]);

        return $message->fresh();
    }

    public function findById(int $id): ?object
    {
        return Message::find($id);
    }

    private function applyTemporalVisibility(Builder $query, int $viewerId): Builder
    {
        $enabledStatus = UserStatus::ENABLED->value;

        return $query
            ->whereExists(function ($q) use ($viewerId) {
                $q->select(DB::raw(1))
                  ->from('group_memberships as gm')
                  ->whereColumn('gm.group_id', 'messages.group_id')
                  ->where('gm.user_id', $viewerId)
                  ->whereColumn('messages.sent_at', '>=', 'gm.joined_at')
                  ->where(function ($q2) {
                      $q2->whereNull('gm.left_at')
                         ->orWhereColumn('messages.sent_at', '<=', 'gm.left_at');
                  });
            })
            ->whereExists(function ($q) use ($viewerId, $enabledStatus) {
                $q->select(DB::raw(1))
                  ->from('user_status_periods as usp')
                  ->where('usp.user_id', $viewerId)
                  ->where('usp.status', $enabledStatus)
                  ->whereColumn('messages.sent_at', '>=', 'usp.start_time')
                  ->where(function ($q2) {
                      $q2->whereNull('usp.end_time')
                         ->orWhereColumn('messages.sent_at', '<=', 'usp.end_time');
                  });
            });
    }

    public function getGroupMessages(int $groupId, int $viewerId, ?string $since = null): iterable
    {
        $query = Message::with('sender')->withCount('readers')->where('group_id', $groupId);

        $query = $this->applyTemporalVisibility($query, $viewerId);

        if ($since !== null) {
            $query->where('sent_at', '>=', $since);
        }

        return $query->orderBy('sent_at', 'desc')->take(500)->reverse()->values();
    }

    public function searchMessages(int $groupId, int $viewerId, string $query): iterable
    {
        $builder = Message::with('sender')->withCount('readers')->where('group_id', $groupId);

        $builder = $this->applyTemporalVisibility($builder, $viewerId);

        $builder->whereRaw("to_tsvector('simple', coalesce(message_text, '')) @@ plainto_tsquery('simple', ?)", [$query]);

        return $builder->orderBy('sent_at', 'desc')->take(500)->get();
    }

    public function markAsRead(int $messageId, int $userId): void
    {
        $record = MessageRead::firstOrCreate([
            'message_id' => $messageId,
            'user_id' => $userId,
        ], [
            'read_at' => now(),
        ]);

        if ($record->wasRecentlyCreated) {
            $message = $this->findById($messageId);

            if ($message) {
                event(new MessageReadEvent(
                    messageId: $messageId,
                    groupId: (int) $message->group_id,
                    userId: $userId,
                ));
            }
        }
    }

    public function getReaders(int $messageId): iterable
    {
        return MessageRead::with('user')->where('message_id', $messageId)->get();
    }
}
