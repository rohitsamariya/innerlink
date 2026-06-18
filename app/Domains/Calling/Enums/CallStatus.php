<?php

declare(strict_types=1);

namespace App\Domains\Calling\Enums;

enum CallStatus: string
{
    case RINGING = 'ringing';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case MISSED = 'missed';
    case ENDED = 'ended';
    case FAILED = 'failed';
}
