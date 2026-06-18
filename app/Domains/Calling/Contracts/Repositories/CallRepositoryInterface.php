<?php

declare(strict_types=1);

namespace App\Domains\Calling\Contracts\Repositories;

use App\Domains\Calling\Models\Call;

interface CallRepositoryInterface
{
    public function create(array $data): Call;

    public function findById(int $id): ?Call;

    public function updateStatus(int $id, string $status, ?array $extra = []): bool;

    public function isParticipant(int $callId, int $userId): bool;

    public function getActiveCallForUser(int $userId): ?Call;

    public function getHistoryForUser(int $userId, int $limit = 50): array;
}
