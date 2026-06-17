<?php

declare(strict_types=1);

namespace App\Domains\Communication\Contracts\Repositories;

interface GroupMembershipRepositoryInterface
{
    /**
     * Check if a user is an active member of a group.
     */
    public function isUserActiveMemberOfGroup(int $userId, int $groupId): bool;

    /**
     * Get all active member user IDs for a group.
     *
     * @return array<int>
     */
    public function getActiveMemberIds(int $groupId): array;
}
