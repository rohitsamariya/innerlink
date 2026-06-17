<?php

declare(strict_types=1);

namespace App\Domains\Admin\Infrastructure\Repositories;

use App\Domains\Admin\Contracts\Repositories\ExportRepositoryInterface;
use App\Domains\Admin\DTOs\ExportConfigData;
use App\Domains\Admin\Models\ExportRequest;
use App\Domains\Admin\Enums\ExportStatus;

class ExportRepository implements ExportRepositoryInterface
{
    public function createRequest(int $adminId, ExportConfigData $config, string $expiresAt): object
    {
        return ExportRequest::create([
            'admin_id' => $adminId,
            'format' => $config->format,
            'type' => $config->type,
            'filters' => $config->filters,
            'status' => ExportStatus::PENDING,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findRequest(int $requestId): ?object
    {
        return ExportRequest::find($requestId);
    }

    public function markProcessing(int $requestId): void
    {
        ExportRequest::where('id', $requestId)->update([
            'status' => ExportStatus::PROCESSING,
        ]);
    }

    public function markCompleted(int $requestId, string $filePath): void
    {
        ExportRequest::where('id', $requestId)->update([
            'status' => ExportStatus::COMPLETED,
            'file_path' => $filePath,
        ]);
    }

    public function markFailed(int $requestId, string $errorMessage, ?string $internalErrorDetails = null): void
    {
        $update = [
            'status' => ExportStatus::FAILED,
            'error_message' => $errorMessage,
        ];

        if ($internalErrorDetails !== null) {
            $update['internal_error_details'] = $internalErrorDetails;
        }

        ExportRequest::where('id', $requestId)->update($update);
    }

    public function getAdminHistory(int $adminId): iterable
    {
        return ExportRequest::where('admin_id', $adminId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
