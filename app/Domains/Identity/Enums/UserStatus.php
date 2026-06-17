<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum UserStatus: string
{
    case ENABLED = 'ENABLED';
    case DISABLED = 'DISABLED';
}
