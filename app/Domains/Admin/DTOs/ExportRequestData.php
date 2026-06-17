<?php

declare(strict_types=1);

namespace App\Domains\Admin\DTOs;

use App\Domains\Admin\Enums\ExportFormat;
use App\Domains\Admin\Enums\ExportStatus;
use DateTimeImmutable;

final readonly class ExportRequestData
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public int $id,
        public int $adminId,
        public ExportFormat $format,
        public ExportStatus $status,
        public array $filters,
        public DateTimeImmutable $expiresAt,
        public ?string $filePath = null,
        public ?string $errorMessage = null
    ) {}
}
