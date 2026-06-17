<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum Role: string
{
    case ADMIN = 'ADMIN';
    case MANAGER = 'MANAGER';
    case EMPLOYEE = 'EMPLOYEE';
}
