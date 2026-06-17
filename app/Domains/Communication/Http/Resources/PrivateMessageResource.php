<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'sender_name' => $this->sender?->full_name,
            'receiver_name' => $this->receiver?->full_name,
            'message_text' => $this->message_text,
            'sent_at' => $this->sent_at instanceof DateTimeInterface
                ? $this->sent_at->format(DateTimeInterface::ATOM)
                : $this->sent_at,
            'read_at' => $this->read_at instanceof DateTimeInterface
                ? $this->read_at->format(DateTimeInterface::ATOM)
                : $this->read_at,
        ];
    }
}
