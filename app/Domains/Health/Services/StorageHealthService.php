<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\DTOs\ServiceStatus;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StorageHealthService
{
    public function check(): ServiceStatus
    {
        try {
            $disk = Storage::disk('local');
            $root = $disk->path('');

            if (!is_dir($root)) {
                return ServiceStatus::down('storage', 'Storage root not found');
            }

            if (!is_readable($root)) {
                return ServiceStatus::down('storage', 'Storage root not readable');
            }

            return ServiceStatus::up('storage');
        } catch (Throwable) {
            return ServiceStatus::down('storage', 'Storage check failed');
        }
    }
}
