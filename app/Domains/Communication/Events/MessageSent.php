<?php

declare(strict_types=1);

namespace App\Domains\Communication\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $id,
        public readonly int $groupId,
        public readonly int $senderId,
        public readonly string $senderName,
        public readonly string $messageText,
        public readonly string $sentAt
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('groups.' . $this->groupId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
