<?php

declare(strict_types=1);

namespace App\Domains\Health\Actions;

use App\Domains\Health\Contracts\HealthExecutionStrategy;
use App\Domains\Health\DTOs\HealthCheckResult;
use App\Domains\Health\Services\DatabaseHealthService;
use App\Domains\Health\Services\QueueHealthService;
use App\Domains\Health\Services\RedisHealthService;
use App\Domains\Health\Services\ReverbHealthService;
use App\Domains\Health\Services\SequentialHealthRunner;
use App\Domains\Health\Services\StorageHealthService;

final readonly class HealthStatusAction
{
    private HealthExecutionStrategy $strategy;

    public function __construct(
        private DatabaseHealthService $databaseHealth,
        private RedisHealthService $redisHealth,
        private QueueHealthService $queueHealth,
        private ReverbHealthService $reverbHealth,
        private StorageHealthService $storageHealth,
        ?HealthExecutionStrategy $strategy = null,
    ) {
        $this->strategy = $strategy ?? new SequentialHealthRunner(
            databaseHealth: $this->databaseHealth,
            redisHealth: $this->redisHealth,
            queueHealth: $this->queueHealth,
            reverbHealth: $this->reverbHealth,
            storageHealth: $this->storageHealth,
        );
    }

    public function execute(): HealthCheckResult
    {
        return $this->strategy->execute();
    }
}
