<?php

declare(strict_types=1);

namespace App\Domains\Communication\Infrastructure\Repositories;

use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\Models\GroupMembership;

class GroupMembershipRepository implements GroupMembershipRepositoryInterface
{
    public function isUserActiveMemberOfGroup(int $userId, int $groupId): bool
    {
        return GroupMembership::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->whereNull('left_at')
            ->exists();
    }

    /** @return array<int> */
    public function getActiveMemberIds(int $groupId): array
    {
        return GroupMembership::where('group_id', $groupId)
            ->whereNull('left_at')
            ->pluck('user_id')
            ->toArray();
    }
}
