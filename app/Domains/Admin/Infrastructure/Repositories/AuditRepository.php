<?php

declare(strict_types=1);

namespace App\Domains\Admin\Infrastructure\Repositories;

use App\Domains\Admin\Contracts\Repositories\AuditRepositoryInterface;
use App\Domains\Admin\Models\AdminAuditLog;

class AuditRepository implements AuditRepositoryInterface
{
    public function insertLog(
        int $adminId,
        string $action,
        ?string $targetType,
        ?int $targetId,
        ?array $payload,
        string $ipAddress,
        string $userAgent
    ): object {
        return AdminAuditLog::create([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function getAuditHistory(int $adminId): iterable
    {
        return AdminAuditLog::where('admin_id', $adminId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTargetHistory(string $targetType, int $targetId): iterable
    {
        return AdminAuditLog::where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
