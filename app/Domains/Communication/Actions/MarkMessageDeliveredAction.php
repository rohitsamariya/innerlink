<?php

declare(strict_types=1);

namespace App\Domains\Communication\Actions;

use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Communication\Events\MessageDelivered;
use App\Domains\Communication\Exceptions\NotGroupMemberException;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class MarkMessageDeliveredAction
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private GroupMembershipRepositoryInterface $membershipRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(int $messageId, int $groupId, int $userId): void
    {
        if (!$this->membershipRepository->isUserActiveMemberOfGroup($userId, $groupId)) {
            throw new NotGroupMemberException('User is not an active member of this group');
        }

        $message = $this->messageRepository->findById($messageId);
        if (!$message || $message->group_id !== $groupId) {
            throw new \RuntimeException('Message not found in this group');
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw UserNotFoundException::forId($userId);
        }

        DB::afterCommit(function () use ($messageId, $groupId, $userId, $user): void {
            event(new MessageDelivered(
                messageId: $messageId,
                groupId: $groupId,
                userId: $userId,
                userName: $user->full_name,
                deliveredAt: now()->toIso8601String(),
            ));
        });
    }
}
