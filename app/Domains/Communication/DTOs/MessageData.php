<?php

declare(strict_types=1);

namespace App\Domains\Communication\DTOs;

use App\Domains\Communication\ValueObjects\MessageContent;

final readonly class MessageData
{
    public function __construct(
        public int $groupId,
        public int $senderId,
        public MessageContent $content
    ) {}
}
