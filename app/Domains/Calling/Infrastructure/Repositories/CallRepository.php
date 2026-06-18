<?php

declare(strict_types=1);

namespace App\Domains\Calling\Infrastructure\Repositories;

use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\Models\Call;

class CallRepository implements CallRepositoryInterface
{
    public function create(array $data): Call
    {
        return Call::query()->create($data);
    }

    public function findById(int $id): ?Call
    {
        return Call::query()->with(['caller', 'receiver'])->find($id);
    }

    public function updateStatus(int $id, string $status, ?array $extra = []): bool
    {
        $data = array_merge(['status' => $status], $extra ?? []);
        return (bool) Call::query()->where('id', $id)->update($data);
    }

    public function isParticipant(int $callId, int $userId): bool
    {
        return Call::query()
            ->where('id', $callId)
            ->where(function ($q) use ($userId) {
                $q->where('caller_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->exists();
    }

    public function getActiveCallForUser(int $userId): ?Call
    {
        return Call::query()
            ->whereIn('status', ['ringing', 'accepted'])
            ->where(function ($q) use ($userId) {
                $q->where('caller_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->with(['caller', 'receiver'])
            ->latest()
            ->first();
    }

    public function getHistoryForUser(int $userId, int $limit = 50): array
    {
        return Call::query()
            ->where(function ($q) use ($userId) {
                $q->where('caller_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->with(['caller', 'receiver'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
