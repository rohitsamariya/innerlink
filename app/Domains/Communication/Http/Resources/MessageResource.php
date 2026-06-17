<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use DateTimeInterface;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $id = isset($this->id) ? $this->id : null;
        $groupId = isset($this->group_id) ? $this->group_id : (isset($this->groupId) ? $this->groupId : null);
        $senderId = isset($this->sender_id) ? $this->sender_id : (isset($this->senderId) ? $this->senderId : null);
        $messageText = isset($this->message_text) ? $this->message_text : (isset($this->messageText) ? $this->messageText : null);
        $sentAt = isset($this->sent_at) ? $this->sent_at : (isset($this->sentAt) ? $this->sentAt : null);
        $sender = $this->sender ?? null;
        $lastSeenAt = $sender?->last_seen_at ?? null;

        return [
            'id' => $id,
            'group_id' => $groupId,
            'sender_id' => $senderId,
            'sender_name' => $sender?->full_name ?? 'Unknown',
            'sender_last_seen_at' => $lastSeenAt
                ? ($lastSeenAt instanceof DateTimeInterface ? $lastSeenAt->format(DateTimeInterface::ATOM) : (is_string($lastSeenAt) ? $lastSeenAt : null))
                : null,
            'message_text' => $messageText,
            'sent_at' => $sentAt
                ? ($sentAt instanceof DateTimeInterface ? $sentAt->format(DateTimeInterface::ATOM) : (is_string($sentAt) ? $sentAt : null))
                : null,
            'readers_count' => isset($this->readers_count) ? (int) $this->readers_count : 0,
        ];
    }
}
