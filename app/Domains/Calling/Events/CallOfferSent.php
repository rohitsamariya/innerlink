<?php

declare(strict_types=1);

namespace App\Domains\Calling\Events;

use App\Domains\Calling\DTOs\CallData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CallOfferSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public CallData $callData) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->callData->receiverId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.offer';
    }

    public function broadcastWith(): array
    {
        return $this->callData->toArray();
    }
}
