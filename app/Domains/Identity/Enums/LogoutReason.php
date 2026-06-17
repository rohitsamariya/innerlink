<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum LogoutReason: string
{
    case USER_INITIATED = 'USER_INITIATED';
    case INACTIVITY = 'INACTIVITY';
    case FORCE_LOGOUT = 'FORCE_LOGOUT';
}
