<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\DTOs\ServiceStatus;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisHealthService
{
    private const TIMEOUT = 2;

    public function check(): ServiceStatus
    {
        try {
            Redis::connection()->ping();

            return ServiceStatus::up('redis');
        } catch (Throwable) {
            return ServiceStatus::down('redis', 'Redis connection failed');
        }
    }
}
