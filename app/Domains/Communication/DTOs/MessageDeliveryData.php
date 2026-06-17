<?php

declare(strict_types=1);

namespace App\Domains\Communication\DTOs;

final readonly class MessageDeliveryData
{
    public function __construct(
        public int $messageId,
        public int $groupId,
        public int $userId,
    ) {}
}
