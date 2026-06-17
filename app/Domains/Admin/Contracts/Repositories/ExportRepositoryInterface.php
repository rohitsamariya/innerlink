<?php

declare(strict_types=1);

namespace App\Domains\Admin\Contracts\Repositories;

use App\Domains\Admin\DTOs\ExportConfigData;

/**
 * Interface ExportRepositoryInterface
 *
 * Contract for Export Request persistence operations in the Admin Domain.
 */
interface ExportRepositoryInterface
{
    /**
     * Create a new export request.
     *
     * @param int $adminId
     * @param ExportConfigData $config
     * @param string $expiresAt ISO-8601 timestamp
     * @return object
     */
    public function createRequest(int $adminId, ExportConfigData $config, string $expiresAt): object;

    /**
     * Find an export request by its ID.
     *
     * @param int $requestId
     * @return object|null
     */
    public function findRequest(int $requestId): ?object;

    /**
     * Mark an export request as processing.
     *
     * @param int $requestId
     * @return void
     */
    public function markProcessing(int $requestId): void;

    /**
     * Mark an export request as completed.
     *
     * @param int $requestId
     * @param string $filePath
     * @return void
     */
    public function markCompleted(int $requestId, string $filePath): void;

    /**
     * Mark an export request as failed.
     *
     * @param int $requestId
     * @param string $errorMessage Public-safe error message for API responses.
     * @param string|null $internalErrorDetails Raw exception details for internal audit (never exposed via API).
     * @return void
     */
    public function markFailed(int $requestId, string $errorMessage, ?string $internalErrorDetails = null): void;

    /**
     * Retrieve export history for a specific admin.
     *
     * @param int $adminId
     * @return iterable<object>
     */
    public function getAdminHistory(int $adminId): iterable;
}
