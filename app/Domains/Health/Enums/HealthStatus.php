<?php

declare(strict_types=1);

namespace App\Domains\Health\Enums;

enum HealthStatus: string
{
    case UP = 'up';
    case DOWN = 'down';
    case DEGRADED = 'degraded';
}
