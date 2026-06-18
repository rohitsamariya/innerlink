<?php

declare(strict_types=1);

namespace App\Domains\Calling\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class IceCandidateSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $callId,
        public array $candidate,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('calls.' . $this->callId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.ice-candidate';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callId,
            'candidate' => $this->candidate,
            'user_id' => $this->userId,
        ];
    }
}
