<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Health\DTOs\ServiceStatus;
use App\Domains\Health\Services\ParallelHealthRunner;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class HealthParallelRunnerTest extends TestCase
{
    public function test_parallel_runner_builds_correct_commands(): void
    {
        Process::fake();

        $runner = new ParallelHealthRunner();
        $runner->execute();

        Process::assertRan(function ($process) {
            $command = $process instanceof \Illuminate\Process\PendingProcess
                ? $process->command
                : (string) $process;

            return str_contains($command, 'php')
                && str_contains($command, 'artisan')
                && str_contains($command, 'health:run');
        });
    }

    public function test_parallel_runner_parses_results_correctly(): void
    {
        $databaseResult = json_encode([
            'name' => 'database', 'status' => 'up', 'message' => null, 'duration_ms' => 150,
        ]);

        $redisResult = json_encode([
            'name' => 'redis', 'status' => 'up', 'message' => null, 'duration_ms' => 12,
        ]);

        $queueResult = json_encode([
            'name' => 'queue', 'status' => 'up', 'message' => null, 'duration_ms' => 8,
        ]);

        $reverbResult = json_encode([
            'name' => 'reverb', 'status' => 'down', 'message' => 'Not configured', 'duration_ms' => 0,
        ]);

        $storageResult = json_encode([
            'name' => 'storage', 'status' => 'up', 'message' => null, 'duration_ms' => 3,
        ]);

        Process::fake([
            '*DatabaseHealthService*' => Process::result(output: $databaseResult),
            '*RedisHealthService*' => Process::result(output: $redisResult),
            '*QueueHealthService*' => Process::result(output: $queueResult),
            '*ReverbHealthService*' => Process::result(output: $reverbResult),
            '*StorageHealthService*' => Process::result(output: $storageResult),
        ]);

        $runner = new ParallelHealthRunner();

        $result = $runner->execute();

        $this->assertSame('degraded', $result->overallStatus());
        $this->assertSame('up', $result->servicesStatus()['database']);
        $this->assertSame('up', $result->servicesStatus()['redis']);
        $this->assertSame('up', $result->servicesStatus()['queue']);
        $this->assertSame('down', $result->servicesStatus()['reverb']);
        $this->assertSame('up', $result->servicesStatus()['storage']);
    }

    public function test_parallel_runner_handles_malformed_output(): void
    {
        Process::fake([
            '*' => Process::result(output: 'not-json'),
        ]);

        $runner = new ParallelHealthRunner();

        $result = $runner->execute();

        $this->assertSame('unhealthy', $result->overallStatus());

        foreach ($result->services as $service) {
            $this->assertSame('down', $service->status->value);
        }
    }

    public function test_parallel_runner_handles_process_failure(): void
    {
        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1,
            ),
        ]);

        $runner = new ParallelHealthRunner();

        $result = $runner->execute();

        $this->assertSame('unhealthy', $result->overallStatus());

        foreach ($result->services as $service) {
            $this->assertSame('down', $service->status->value);
        }
    }
}
