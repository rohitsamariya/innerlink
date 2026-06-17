<?php

declare(strict_types=1);

namespace App\Domains\Identity\DTOs;

use DateTimeImmutable;

final readonly class SessionData
{
    public function __construct(
        public int $userId,
        public string $ipAddress,
        public string $userAgent,
        public DateTimeImmutable $loggedInAt
    ) {}
}
