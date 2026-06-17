<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_live_returns_200_and_alive_status(): void
    {
        $response = $this->getJson('/api/health/live');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'alive',
        ]);
    }

    public function test_health_live_has_no_additional_fields(): void
    {
        $response = $this->getJson('/api/health/live');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_health_ready_returns_200_with_valid_structure(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('services', $json);
        $this->assertArrayHasKey('metrics', $json);

        $this->assertContains($json['status'], ['healthy', 'degraded', 'unhealthy']);

        $services = $json['services'];
        $this->assertCount(5, $services);
        $this->assertArrayHasKey('database', $services);
        $this->assertArrayHasKey('redis', $services);
        $this->assertArrayHasKey('queue', $services);
        $this->assertArrayHasKey('reverb', $services);
        $this->assertArrayHasKey('storage', $services);

        foreach ($services as $name => $status) {
            $this->assertContains($status, ['up', 'down', 'degraded'], "Service {$name} has invalid status");
        }
    }

    public function test_health_ready_returns_valid_metrics(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200);

        $metrics = $response->json('metrics');

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('generated_at', $metrics);
        $this->assertArrayHasKey('execution_time_ms', $metrics);
        $this->assertArrayHasKey('service_count', $metrics);
        $this->assertArrayHasKey('healthy_count', $metrics);
        $this->assertArrayHasKey('degraded_count', $metrics);
        $this->assertArrayHasKey('failed_count', $metrics);
        $this->assertArrayHasKey('slowest_service', $metrics);
        $this->assertArrayHasKey('slowest_service_duration_ms', $metrics);
        $this->assertArrayHasKey('average_service_duration_ms', $metrics);

        $this->assertIsString($metrics['generated_at']);
        $this->assertIsInt($metrics['execution_time_ms']);
        $this->assertIsInt($metrics['service_count']);
        $this->assertIsInt($metrics['healthy_count']);
        $this->assertIsInt($metrics['degraded_count']);
        $this->assertIsInt($metrics['failed_count']);
        $this->assertIsString($metrics['slowest_service']);
        $this->assertIsInt($metrics['slowest_service_duration_ms']);
        $this->assertIsInt($metrics['average_service_duration_ms']);

        $this->assertSame(5, $metrics['service_count']);
        $this->assertSame(
            $metrics['healthy_count'] + $metrics['degraded_count'] + $metrics['failed_count'],
            $metrics['service_count']
        );
    }

    public function test_health_ready_metrics_execution_time_is_positive(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200);

        $executionTime = $response->json('metrics.execution_time_ms');

        $this->assertGreaterThanOrEqual(0, $executionTime);
    }

    public function test_health_ready_metrics_includes_performance_data(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200);

        $metrics = $response->json('metrics');

        $this->assertNotEmpty($metrics['slowest_service']);
        $this->assertGreaterThan(0, $metrics['slowest_service_duration_ms']);
        $this->assertGreaterThan(0, $metrics['average_service_duration_ms']);
    }

    public function test_health_endpoints_include_security_headers(): void
    {
        $liveResponse = $this->getJson('/api/health/live');
        $readyResponse = $this->getJson('/api/health/ready');

        foreach ([$liveResponse, $readyResponse] as $response) {
            $response->assertStatus(200);
            $response->assertHeader('X-Robots-Tag', 'noindex');
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_health_endpoints_disable_caching(): void
    {
        $liveResponse = $this->getJson('/api/health/live');
        $readyResponse = $this->getJson('/api/health/ready');

        foreach ([$liveResponse, $readyResponse] as $response) {
            $response->assertStatus(200);

            $cacheControl = $response->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cacheControl);
        }
    }

    public function test_health_ready_cache_returns_same_result_within_ttl(): void
    {
        $first = $this->getJson('/api/health/ready');
        $second = $this->getJson('/api/health/ready');

        $first->assertStatus(200);
        $second->assertStatus(200);

        $this->assertSame($first->json('metrics.generated_at'), $second->json('metrics.generated_at'));
    }

    public function test_health_live_is_never_cached(): void
    {
        $first = $this->getJson('/api/health/live');
        $second = $this->getJson('/api/health/live');

        $first->assertStatus(200);
        $second->assertStatus(200);

        $this->assertSame('alive', $first->json('status'));
        $this->assertSame('alive', $second->json('status'));
    }

    public function test_health_ready_never_exposes_sensitive_data(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200);

        $body = $response->content();

        $sensitivePatterns = [
            'localhost',
            '127.0.0.1',
            'password',
            'secret',
            'PONG',
            'SELECT',
            'SQLSTATE',
            'stack trace',
            'host=',
            'port=',
            'dbname=',
            's3://',
            '.com',
        ];

        foreach ($sensitivePatterns as $pattern) {
            $this->assertStringNotContainsStringIgnoringCase(
                $pattern,
                $body,
                "Response must not contain sensitive pattern: {$pattern}"
            );
        }
    }

    public function test_health_ready_services_are_flat_strings_not_objects(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200);

        $services = $response->json('services');

        foreach ($services as $name => $status) {
            $this->assertIsString($status, "Service {$name} status must be a string");
        }
    }
}
