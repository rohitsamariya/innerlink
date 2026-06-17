<?php

declare(strict_types=1);

namespace App\Domains\Communication\Policies;

use App\Domains\Communication\Models\Group;
use App\Domains\Identity\Models\User;

use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;

class GroupPolicy
{
    public function __construct(
        private readonly GroupMembershipRepositoryInterface $membershipRepository
    ) {}

    /**
     * Determine whether the user can view the group.
     */
    public function view(User $user, Group $group): bool
    {
        if (!$user->is_enabled) {
            return false;
        }

        return $this->membershipRepository->isUserActiveMemberOfGroup($user->id, $group->id);
    }
}
