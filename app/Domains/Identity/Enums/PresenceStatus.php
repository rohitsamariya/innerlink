<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum PresenceStatus: string
{
    case ONLINE = 'ONLINE';
    case OFFLINE = 'OFFLINE';
}
