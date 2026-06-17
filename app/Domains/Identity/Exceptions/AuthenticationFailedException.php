<?php

declare(strict_types=1);

namespace App\Domains\Identity\Exceptions;

use Exception;

final class AuthenticationFailedException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid email or password.');
    }

    public static function userDisabled(): self
    {
        return new self('This account has been disabled.');
    }
}
