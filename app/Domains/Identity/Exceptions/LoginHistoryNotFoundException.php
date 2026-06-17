<?php

declare(strict_types=1);

namespace App\Domains\Identity\Exceptions;

use Exception;

final class LoginHistoryNotFoundException extends Exception
{
    public static function forId(int $loginHistoryId): self
    {
        return new self("Login history [ID: {$loginHistoryId}] not found.");
    }
}
