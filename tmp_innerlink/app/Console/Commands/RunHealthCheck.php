<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class RunHealthCheck extends Command
{
    protected $signature = 'health:run {service}';

    protected $description = 'Run a single health service check and output JSON result';

    public function handle(): int
    {
        $serviceClass = $this->argument('service');

        if (!class_exists($serviceClass)) {
            $this->error('Service class not found: ' . $serviceClass);

            return 1;
        }

        $service = app($serviceClass);

        $start = microtime(true);

        try {
            $result = $service->check();
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            $this->line(json_encode([
                'name' => $result->name,
                'status' => $result->status->value,
                'message' => $result->message,
                'duration_ms' => $durationMs,
            ]));

            return 0;
        } catch (Throwable $e) {
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            $this->line(json_encode([
                'name' => $serviceClass,
                'status' => 'down',
                'message' => 'Service check failed',
                'duration_ms' => $durationMs,
            ]));

            return 0;
        }
    }
}
