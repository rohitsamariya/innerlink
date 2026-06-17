<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\DTOs\ServiceStatus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class QueueHealthService
{
    private const BACKLOG_DEGRADED = 1000;
    private const BACKLOG_DOWN = 5000;

    public function check(): ServiceStatus
    {
        try {
            Redis::connection()->ping();
        } catch (Throwable) {
            return ServiceStatus::down('queue', 'Queue connection failed');
        }

        try {
            $backlog = Queue::size();

            return match (true) {
                $backlog > self::BACKLOG_DOWN => ServiceStatus::down('queue', 'Queue backlog critical'),
                $backlog > self::BACKLOG_DEGRADED => ServiceStatus::degraded('queue', 'Queue backlog elevated'),
                default => ServiceStatus::up('queue'),
            };
        } catch (Throwable) {
            return ServiceStatus::degraded('queue', 'Unable to read queue size');
        }
    }
}
