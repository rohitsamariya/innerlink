<?php

declare(strict_types=1);

namespace App\Domains\Health\DTOs;

use App\Domains\Health\Enums\HealthStatus;

final readonly class HealthCheckResult
{
    private const CRITICAL_SERVICES = ['database', 'redis', 'storage'];

    /** @param ServiceStatus[] $services */
    public function __construct(
        public array $services,
    ) {}

    public function overallStatus(): string
    {
        $hasCriticalDown = false;
        $hasDown = false;
        $hasDegraded = false;

        foreach ($this->services as $service) {
            if ($service->status === HealthStatus::DOWN) {
                $hasDown = true;
                if (in_array($service->name, self::CRITICAL_SERVICES, true)) {
                    $hasCriticalDown = true;
                }
            }
            if ($service->status === HealthStatus::DEGRADED) {
                $hasDegraded = true;
            }
        }

        if ($hasCriticalDown) {
            return 'unhealthy';
        }

        if ($hasDown || $hasDegraded) {
            return 'degraded';
        }

        return 'healthy';
    }

    /** @return array<string, string> */
    public function servicesStatus(): array
    {
        $result = [];

        foreach ($this->services as $service) {
            $result[$service->name] = $service->status->value;
        }

        return $result;
    }

    public function isCritical(string $serviceName): bool
    {
        return in_array($serviceName, self::CRITICAL_SERVICES, true);
    }
}
