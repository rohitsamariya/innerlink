<?php

declare(strict_types=1);

namespace App\Domains\Health\DTOs;

use App\Domains\Health\Enums\HealthStatus;

final readonly class ServiceStatus
{
    private function __construct(
        public string $name,
        public HealthStatus $status,
        public ?string $message = null,
        public ?int $durationMs = null,
    ) {}

    public static function up(string $name, ?int $durationMs = null): self
    {
        return new self($name, HealthStatus::UP, null, $durationMs);
    }

    public static function down(string $name, string $message, ?int $durationMs = null): self
    {
        return new self($name, HealthStatus::DOWN, $message, $durationMs);
    }

    public static function degraded(string $name, string $message, ?int $durationMs = null): self
    {
        return new self($name, HealthStatus::DEGRADED, $message, $durationMs);
    }

    /** @return array{name: string, status: string, message: string|null, duration_ms: int|null} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'message' => $this->message,
            'duration_ms' => $this->durationMs,
        ];
    }

    /** @param array{name: string, status: string, message?: string|null, duration_ms?: int|null} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            HealthStatus::from($data['status']),
            $data['message'] ?? null,
            $data['duration_ms'] ?? null,
        );
    }
}
