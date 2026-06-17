<?php

declare(strict_types=1);

namespace App\Domains\Admin\Enums;

enum ExportFormat: string
{
    case CSV = 'CSV';
    case XLSX = 'XLSX';
    case PDF = 'PDF';
}
