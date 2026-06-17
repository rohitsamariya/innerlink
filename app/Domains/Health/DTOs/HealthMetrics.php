<?php

declare(strict_types=1);

namespace App\Domains\Health\DTOs;

final readonly class HealthMetrics
{
    public function __construct(
        public string $generatedAt,
        public int $executionTimeMs,
        public int $serviceCount,
        public int $healthyCount,
        public int $degradedCount,
        public int $failedCount,
        public string $slowestService = '',
        public int $slowestServiceDurationMs = 0,
        public int $averageServiceDurationMs = 0,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'execution_time_ms' => $this->executionTimeMs,
            'service_count' => $this->serviceCount,
            'healthy_count' => $this->healthyCount,
            'degraded_count' => $this->degradedCount,
            'failed_count' => $this->failedCount,
            'slowest_service' => $this->slowestService,
            'slowest_service_duration_ms' => $this->slowestServiceDurationMs,
            'average_service_duration_ms' => $this->averageServiceDurationMs,
        ];
    }
}
