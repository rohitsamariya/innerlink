<?php

declare(strict_types=1);

namespace App\Domains\Communication\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTypingStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $userName,
        public readonly int $groupId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('groups.' . $this->groupId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'typing.started';
    }
}
