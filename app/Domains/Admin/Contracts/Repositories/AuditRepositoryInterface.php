<?php

declare(strict_types=1);

namespace App\Domains\Admin\Contracts\Repositories;

/**
 * Interface AuditRepositoryInterface
 *
 * Contract for append-only audit persistence in the Admin Domain.
 */
interface AuditRepositoryInterface
{
    /**
     * Insert a new audit log entry (append-only).
     *
     * @param int $adminId
     * @param string $action
     * @param string|null $targetType
     * @param int|null $targetId
     * @param array<string, mixed>|null $payload
     * @param string $ipAddress
     * @param string $userAgent
     * @return object
     */
    public function insertLog(
        int $adminId,
        string $action,
        ?string $targetType,
        ?int $targetId,
        ?array $payload,
        string $ipAddress,
        string $userAgent
    ): object;

    /**
     * Retrieve audit history for a specific admin.
     *
     * @param int $adminId
     * @return iterable<object>
     */
    public function getAuditHistory(int $adminId): iterable;

    /**
     * Retrieve audit history for a specific target entity.
     *
     * @param string $targetType
     * @param int $targetId
     * @return iterable<object>
     */
    public function getTargetHistory(string $targetType, int $targetId): iterable;
}
