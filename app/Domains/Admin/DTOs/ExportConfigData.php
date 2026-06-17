<?php

declare(strict_types=1);

namespace App\Domains\Admin\DTOs;

use App\Domains\Admin\Enums\ExportFormat;

final readonly class ExportConfigData
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public ExportFormat $format,
        public string $type,
        public array $filters = []
    ) {}
}
