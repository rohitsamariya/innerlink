<?php

declare(strict_types=1);

namespace App\Domains\Health\Http\Controllers;

use App\Domains\Health\Actions\HealthStatusAction;
use App\Domains\Health\DTOs\HealthMetrics;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    private const CACHE_TTL = 5;

    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
        ]);
    }

    public function ready(HealthStatusAction $action): JsonResponse
    {
        $start = microtime(true);

        $result = Cache::remember('health:ready', self::CACHE_TTL, function () use ($action) {
            return $action->execute();
        });

        $executionTimeMs = (int) ((microtime(true) - $start) * 1000);

        $services = $result->servicesStatus();
        $statuses = $result->services;

        $durations = array_map(fn($s) => $s->durationMs ?? 0, $statuses);
        $durations = array_filter($durations, fn(int $d): bool => $d > 0);

        $slowestService = '';
        $slowestDuration = 0;
        $averageDuration = 0;

        if ($durations !== []) {
            $averageDuration = (int) (array_sum($durations) / count($durations));
            $maxDuration = max($durations);
            $slowestDuration = $maxDuration;

            foreach ($statuses as $s) {
                if (($s->durationMs ?? 0) === $maxDuration) {
                    $slowestService = $s->name;
                    break;
                }
            }
        }

        $metrics = new HealthMetrics(
            generatedAt: now()->toIso8601String(),
            executionTimeMs: $executionTimeMs,
            serviceCount: count($services),
            healthyCount: count(array_filter($services, fn(string $s): bool => $s === 'up')),
            degradedCount: count(array_filter($services, fn(string $s): bool => $s === 'degraded')),
            failedCount: count(array_filter($services, fn(string $s): bool => $s === 'down')),
            slowestService: $slowestService,
            slowestServiceDurationMs: $slowestDuration,
            averageServiceDurationMs: $averageDuration,
        );

        return response()->json([
            'status' => $result->overallStatus(),
            'services' => $services,
            'metrics' => $metrics->toArray(),
        ]);
    }
}
