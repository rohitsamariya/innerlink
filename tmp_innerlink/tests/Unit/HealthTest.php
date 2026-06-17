<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Domains\Health\Enums\HealthStatus;
use App\Domains\Health\DTOs\ServiceStatus;
use App\Domains\Health\DTOs\HealthCheckResult;
use App\Domains\Health\Services\DatabaseHealthService;
use App\Domains\Health\Services\RedisHealthService;
use App\Domains\Health\Services\QueueHealthService;
use App\Domains\Health\Services\ReverbHealthService;
use App\Domains\Health\Services\StorageHealthService;
use App\Domains\Health\Actions\HealthStatusAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Mockery;

class HealthTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_health_status_enum_has_correct_values(): void
    {
        $this->assertSame('up', HealthStatus::UP->value);
        $this->assertSame('down', HealthStatus::DOWN->value);
        $this->assertSame('degraded', HealthStatus::DEGRADED->value);
    }

    public function test_service_status_up_factory(): void
    {
        $status = ServiceStatus::up('database');

        $this->assertSame('database', $status->name);
        $this->assertSame(HealthStatus::UP, $status->status);
        $this->assertNull($status->message);
    }

    public function test_service_status_down_factory(): void
    {
        $status = ServiceStatus::down('redis', 'Connection failed');

        $this->assertSame('redis', $status->name);
        $this->assertSame(HealthStatus::DOWN, $status->status);
        $this->assertSame('Connection failed', $status->message);
    }

    public function test_service_status_degraded_factory(): void
    {
        $status = ServiceStatus::degraded('queue', 'Backlog elevated');

        $this->assertSame('queue', $status->name);
        $this->assertSame(HealthStatus::DEGRADED, $status->status);
        $this->assertSame('Backlog elevated', $status->message);
    }

    public function test_health_check_result_all_up_returns_healthy(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::up('redis'),
        ]);

        $this->assertSame('healthy', $result->overallStatus());
    }

    public function test_health_check_result_critical_down_returns_unhealthy(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::down('redis', 'Connection failed'),
            ServiceStatus::up('queue'),
        ]);

        $this->assertSame('unhealthy', $result->overallStatus());
    }

    public function test_health_check_result_non_critical_down_returns_degraded(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::up('redis'),
            ServiceStatus::down('reverb', 'Not configured'),
            ServiceStatus::up('queue'),
            ServiceStatus::up('storage'),
        ]);

        $this->assertSame('degraded', $result->overallStatus());
    }

    public function test_health_check_result_degraded_returns_degraded(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::degraded('queue', 'Backlog elevated'),
            ServiceStatus::up('redis'),
        ]);

        $this->assertSame('degraded', $result->overallStatus());
    }

    public function test_health_check_result_mixed_down_critical_wins(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::degraded('queue', 'Backlog elevated'),
            ServiceStatus::down('redis', 'Connection failed'),
        ]);

        $this->assertSame('unhealthy', $result->overallStatus());
    }

    public function test_health_check_result_services_status_format(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::down('redis', 'Connection failed'),
            ServiceStatus::degraded('queue', 'Backlog elevated'),
        ]);

        $services = $result->servicesStatus();

        $this->assertSame('up', $services['database']);
        $this->assertSame('down', $services['redis']);
        $this->assertSame('degraded', $services['queue']);
        $this->assertCount(3, $services);
    }

    public function test_database_health_service_returns_up_when_db_works(): void
    {
        $mockConn = Mockery::mock('Illuminate\Database\Connection');
        $mockConn->shouldReceive('getDriverName')->andReturn('sqlite');
        $mockConn->shouldReceive('statement')
            ->with('SELECT 1')
            ->andReturn(true);

        DB::shouldReceive('connection')->andReturn($mockConn);

        $service = new DatabaseHealthService();
        $result = $service->check();

        $this->assertSame('database', $result->name);
        $this->assertSame(HealthStatus::UP, $result->status);
    }

    public function test_database_health_service_returns_down_on_exception(): void
    {
        $mockConn = Mockery::mock('Illuminate\Database\Connection');
        $mockConn->shouldReceive('getDriverName')->andReturn('sqlite');
        $mockConn->shouldReceive('statement')
            ->with('SELECT 1')
            ->andThrow(new \PDOException('Connection refused'));

        DB::shouldReceive('connection')->andReturn($mockConn);

        $service = new DatabaseHealthService();
        $result = $service->check();

        $this->assertSame('database', $result->name);
        $this->assertSame(HealthStatus::DOWN, $result->status);
        $this->assertNotNull($result->message);
    }

    public function test_redis_health_service_returns_up_when_ping_succeeds(): void
    {
        $redisMock = Mockery::mock();
        $redisMock->shouldReceive('ping')->once()->andReturn('PONG');

        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($redisMock);

        $service = new RedisHealthService();
        $result = $service->check();

        $this->assertSame('redis', $result->name);
        $this->assertSame(HealthStatus::UP, $result->status);
    }

    public function test_redis_health_service_returns_down_on_exception(): void
    {
        Redis::shouldReceive('connection')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $service = new RedisHealthService();
        $result = $service->check();

        $this->assertSame('redis', $result->name);
        $this->assertSame(HealthStatus::DOWN, $result->status);
        $this->assertNotNull($result->message);
    }

    public function test_storage_health_service_returns_up_when_local_disk_accessible(): void
    {
        Storage::shouldReceive('disk->path')
            ->once()
            ->with('')
            ->andReturn(sys_get_temp_dir());

        $service = new StorageHealthService();
        $result = $service->check();

        $this->assertSame('storage', $result->name);
        $this->assertSame(HealthStatus::UP, $result->status);
    }

    public function test_storage_health_service_returns_down_on_exception(): void
    {
        Storage::shouldReceive('disk->path')
            ->once()
            ->with('')
            ->andThrow(new \RuntimeException('Disk not found'));

        $service = new StorageHealthService();
        $result = $service->check();

        $this->assertSame('storage', $result->name);
        $this->assertSame(HealthStatus::DOWN, $result->status);
    }

    public function test_health_status_action_aggregates_all_services(): void
    {
        $dbService = Mockery::mock(DatabaseHealthService::class);
        $redisService = Mockery::mock(RedisHealthService::class);
        $queueService = Mockery::mock(QueueHealthService::class);
        $reverbService = Mockery::mock(ReverbHealthService::class);
        $storageService = Mockery::mock(StorageHealthService::class);

        $dbService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('database'));
        $redisService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('redis'));
        $queueService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('queue'));
        $reverbService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('reverb'));
        $storageService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('storage'));

        $action = new HealthStatusAction($dbService, $redisService, $queueService, $reverbService, $storageService);
        $result = $action->execute();

        $this->assertSame('healthy', $result->overallStatus());
        $this->assertCount(5, $result->services);
        $this->assertArrayHasKey('database', $result->servicesStatus());
        $this->assertArrayHasKey('redis', $result->servicesStatus());
        $this->assertArrayHasKey('queue', $result->servicesStatus());
        $this->assertArrayHasKey('reverb', $result->servicesStatus());
        $this->assertArrayHasKey('storage', $result->servicesStatus());
    }

    public function test_health_status_action_maps_exceptions_to_safe_responses(): void
    {
        $dbService = Mockery::mock(DatabaseHealthService::class);
        $redisService = Mockery::mock(RedisHealthService::class);
        $queueService = Mockery::mock(QueueHealthService::class);
        $reverbService = Mockery::mock(ReverbHealthService::class);
        $storageService = Mockery::mock(StorageHealthService::class);

        $dbService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('database'));
        $redisService->shouldReceive('check')->once()->andReturn(ServiceStatus::down('redis', 'Connection failed'));
        $queueService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('queue'));
        $reverbService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('reverb'));
        $storageService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('storage'));

        $action = new HealthStatusAction($dbService, $redisService, $queueService, $reverbService, $storageService);
        $result = $action->execute();

        $this->assertSame('unhealthy', $result->overallStatus());
        $this->assertSame('down', $result->servicesStatus()['redis']);
    }

    public function test_service_status_is_readonly(): void
    {
        $status = ServiceStatus::up('database');

        $reflection = new \ReflectionClass($status);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue(property_exists($status, 'name'));
        $this->assertTrue(property_exists($status, 'status'));
        $this->assertTrue(property_exists($status, 'message'));
        $this->assertTrue(property_exists($status, 'durationMs'));
    }

    public function test_service_status_to_array_and_from_array(): void
    {
        $original = ServiceStatus::up('database', 42);
        $array = $original->toArray();
        $restored = ServiceStatus::fromArray($array);

        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->status, $restored->status);
        $this->assertSame($original->message, $restored->message);
        $this->assertSame($original->durationMs, $restored->durationMs);
    }

    public function test_service_status_from_array_handles_missing_fields(): void
    {
        $restored = ServiceStatus::fromArray([
            'name' => 'redis',
            'status' => 'up',
        ]);

        $this->assertSame('redis', $restored->name);
        $this->assertSame(HealthStatus::UP, $restored->status);
        $this->assertNull($restored->message);
        $this->assertNull($restored->durationMs);
    }

    public function test_health_check_result_critical_helper(): void
    {
        $result = new HealthCheckResult([
            ServiceStatus::up('database'),
            ServiceStatus::up('redis'),
        ]);

        $this->assertTrue($result->isCritical('database'));
        $this->assertTrue($result->isCritical('redis'));
        $this->assertTrue($result->isCritical('storage'));
        $this->assertFalse($result->isCritical('queue'));
        $this->assertFalse($result->isCritical('reverb'));
    }

    public function test_sequential_runner_measures_durations(): void
    {
        $dbMock = Mockery::mock(DatabaseHealthService::class);
        $dbMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('database'));

        $redisMock = Mockery::mock(RedisHealthService::class);
        $redisMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('redis'));

        $queueMock = Mockery::mock(QueueHealthService::class);
        $queueMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('queue'));

        $reverbMock = Mockery::mock(ReverbHealthService::class);
        $reverbMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('reverb'));

        $storageMock = Mockery::mock(StorageHealthService::class);
        $storageMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('storage'));

        $runner = new \App\Domains\Health\Services\SequentialHealthRunner(
            databaseHealth: $dbMock,
            redisHealth: $redisMock,
            queueHealth: $queueMock,
            reverbHealth: $reverbMock,
            storageHealth: $storageMock,
        );

        $result = $runner->execute();

        $this->assertSame('healthy', $result->overallStatus());
        $this->assertCount(5, $result->services);

        foreach ($result->services as $service) {
            $this->assertNotNull($service->durationMs);
            $this->assertGreaterThanOrEqual(0, $service->durationMs);
        }
    }

    public function test_sequential_runner_isolates_failures(): void
    {
        $dbMock = Mockery::mock(DatabaseHealthService::class);
        $dbMock->shouldReceive('check')->once()->andThrow(new \RuntimeException('DB crash'));

        $redisMock = Mockery::mock(RedisHealthService::class);
        $redisMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('redis'));

        $queueMock = Mockery::mock(QueueHealthService::class);
        $queueMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('queue'));

        $reverbMock = Mockery::mock(ReverbHealthService::class);
        $reverbMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('reverb'));

        $storageMock = Mockery::mock(StorageHealthService::class);
        $storageMock->shouldReceive('check')->once()->andReturn(ServiceStatus::up('storage'));

        $runner = new \App\Domains\Health\Services\SequentialHealthRunner(
            databaseHealth: $dbMock,
            redisHealth: $redisMock,
            queueHealth: $queueMock,
            reverbHealth: $reverbMock,
            storageHealth: $storageMock,
        );

        $result = $runner->execute();

        $this->assertSame('unhealthy', $result->overallStatus());
        $this->assertCount(5, $result->services);
        $this->assertSame('down', $result->servicesStatus()['database']);
        $this->assertSame('up', $result->servicesStatus()['redis']);
        $this->assertSame('up', $result->servicesStatus()['queue']);
        $this->assertSame('up', $result->servicesStatus()['reverb']);
        $this->assertSame('up', $result->servicesStatus()['storage']);
    }

    public function test_health_status_action_uses_sequential_runner_by_default(): void
    {
        $dbService = Mockery::mock(DatabaseHealthService::class);
        $redisService = Mockery::mock(RedisHealthService::class);
        $queueService = Mockery::mock(QueueHealthService::class);
        $reverbService = Mockery::mock(ReverbHealthService::class);
        $storageService = Mockery::mock(StorageHealthService::class);

        $dbService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('database'));
        $redisService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('redis'));
        $queueService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('queue'));
        $reverbService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('reverb'));
        $storageService->shouldReceive('check')->once()->andReturn(ServiceStatus::up('storage'));

        $action = new \App\Domains\Health\Actions\HealthStatusAction(
            $dbService, $redisService, $queueService, $reverbService, $storageService,
        );
        $result = $action->execute();

        $this->assertSame('healthy', $result->overallStatus());
        $this->assertCount(5, $result->services);
    }
}
