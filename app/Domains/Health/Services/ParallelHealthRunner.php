<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\Contracts\HealthExecutionStrategy;
use App\Domains\Health\DTOs\HealthCheckResult;
use App\Domains\Health\DTOs\ServiceStatus;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

final class ParallelHealthRunner implements HealthExecutionStrategy
{
    private const SERVICE_CLASSES = [
        'database' => DatabaseHealthService::class,
        'redis' => RedisHealthService::class,
        'queue' => QueueHealthService::class,
        'reverb' => ReverbHealthService::class,
        'storage' => StorageHealthService::class,
    ];

    public function execute(): HealthCheckResult
    {
        $start = microtime(true);

        $results = Process::pool(function (Pool $pool) {
            foreach (self::SERVICE_CLASSES as $name => $class) {
                $pool->as($name)->command("php " . base_path('artisan') . " health:run {$class}");
            }
        })->start()->wait();

        $services = [];

        foreach (self::SERVICE_CLASSES as $name => $class) {
            $poolResult = $results[$name] ?? null;
            $output = $poolResult?->output() ?? '';

            $data = json_decode($output, true);

            if (!is_array($data) || !isset($data['name'], $data['status'])) {
                $services[] = ServiceStatus::down($name, 'Parallel check failed', 0);

                continue;
            }

            $services[] = ServiceStatus::fromArray($data);
        }

        $totalDurationMs = (int) ((microtime(true) - $start) * 1000);

        $result = new HealthCheckResult($services);

        return $result;
    }
}
