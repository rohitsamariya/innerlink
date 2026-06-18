<?php

declare(strict_types=1);

namespace App\Domains\Calling\DTOs;

final readonly class CallData
{
    public function __construct(
        public int $callId,
        public int $callerId,
        public string $callerName,
        public int $receiverId,
        public string $receiverName,
        public string $status,
        public ?string $startedAt = null,
        public ?string $endedAt = null,
        public ?int $durationSeconds = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->callId,
            'caller_id' => $this->callerId,
            'caller_name' => $this->callerName,
            'receiver_id' => $this->receiverId,
            'receiver_name' => $this->receiverName,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'ended_at' => $this->endedAt,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
