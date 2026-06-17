<?php

declare(strict_types=1);

namespace App\Domains\Communication\Events;

use App\Domains\Communication\Models\PrivateMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PrivateMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public PrivateMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->message->receiver_id),
            new PrivateChannel('users.' . $this->message->sender_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'private.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'sender_name' => $this->message->sender?->full_name,
            'message_text' => $this->message->message_text,
            'sent_at' => $this->message->sent_at?->toIso8601String(),
        ];
    }
}
