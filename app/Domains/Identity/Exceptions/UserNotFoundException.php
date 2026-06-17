<?php

declare(strict_types=1);

namespace App\Domains\Identity\Exceptions;

use Exception;

final class UserNotFoundException extends Exception
{
    public static function forId(int $userId): self
    {
        return new self("User [ID: {$userId}] not found.");
    }
}
