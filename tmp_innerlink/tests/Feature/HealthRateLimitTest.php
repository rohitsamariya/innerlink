<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HealthRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('health:ready');
    }

    public function test_health_live_allows_120_requests_per_minute(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $response = $this->getJson('/api/health/live');
            $response->assertStatus(200);
        }
    }

    public function test_health_live_blocks_121st_request(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/health/live');
        }

        $response = $this->getJson('/api/health/live');
        $response->assertStatus(429);
    }

    public function test_health_ready_allows_30_requests_per_minute(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson('/api/health/ready');
            $response->assertStatus(200);
        }
    }

    public function test_health_ready_blocks_31st_request(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/health/ready');
        }

        $response = $this->getJson('/api/health/ready');
        $response->assertStatus(429);
    }

    public function test_health_rate_limit_uses_separate_counters(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/health/ready');
        }

        $liveResponse = $this->getJson('/api/health/live');
        $liveResponse->assertStatus(200);

        $readyResponse = $this->getJson('/api/health/ready');
        $readyResponse->assertStatus(429);
    }

    public function test_health_rate_limit_response_has_retry_after_header(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/health/ready');
        }

        $response = $this->getJson('/api/health/ready');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }
}
