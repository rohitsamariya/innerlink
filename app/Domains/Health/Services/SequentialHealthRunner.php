<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\Contracts\HealthExecutionStrategy;
use App\Domains\Health\DTOs\HealthCheckResult;
use App\Domains\Health\DTOs\ServiceStatus;
use Throwable;

final readonly class SequentialHealthRunner implements HealthExecutionStrategy
{
    public function __construct(
        private DatabaseHealthService $databaseHealth,
        private RedisHealthService $redisHealth,
        private QueueHealthService $queueHealth,
        private ReverbHealthService $reverbHealth,
        private StorageHealthService $storageHealth,
    ) {}

    public function execute(): HealthCheckResult
    {
        $results = [];

        foreach ($this->getChecks() as $name => $service) {
            $start = microtime(true);

            try {
                $status = $service->check();
                $durationMs = (int) ((microtime(true) - $start) * 1000);
                $results[] = ServiceStatus::fromArray([
                    'name' => $status->name,
                    'status' => $status->status->value,
                    'message' => $status->message,
                    'duration_ms' => $durationMs,
                ]);
            } catch (Throwable) {
                $durationMs = (int) ((microtime(true) - $start) * 1000);
                $results[] = ServiceStatus::down($name, 'Service check failed', $durationMs);
            }
        }

        return new HealthCheckResult($results);
    }

    /** @return array<string, object> */
    private function getChecks(): array
    {
        return [
            'database' => $this->databaseHealth,
            'redis' => $this->redisHealth,
            'queue' => $this->queueHealth,
            'reverb' => $this->reverbHealth,
            'storage' => $this->storageHealth,
        ];
    }
}
