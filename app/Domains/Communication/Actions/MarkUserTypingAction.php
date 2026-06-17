<?php

declare(strict_types=1);

namespace App\Domains\Communication\Actions;

use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\Events\UserTypingStarted;
use App\Domains\Communication\Events\UserTypingStopped;
use App\Domains\Communication\Exceptions\NotGroupMemberException;
final readonly class MarkUserTypingAction
{
    public function __construct(
        private GroupMembershipRepositoryInterface $membershipRepository,
    ) {}

    public function execute(int $groupId, int $userId, string $userName, string $action): void
    {
        if (!in_array($action, ['started', 'stopped'], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid typing action "%s". Must be "started" or "stopped".', $action));
        }

        if (!$this->membershipRepository->isUserActiveMemberOfGroup($userId, $groupId)) {
            throw new NotGroupMemberException(\sprintf('User %d is not an active member of group %d.', $userId, $groupId));
        }

        if ($action === 'started') {
            event(new UserTypingStarted(
                userId: $userId,
                userName: $userName,
                groupId: $groupId,
            ));
        } else {
            event(new UserTypingStopped(
                userId: $userId,
                groupId: $groupId,
            ));
        }
    }
}
