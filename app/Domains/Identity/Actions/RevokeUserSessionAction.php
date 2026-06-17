<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\Events\ForceDisconnectEvent;
use App\Domains\Identity\Exceptions\LoginHistoryNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class RevokeUserSessionAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Disable the active session for the login history record and broadcast the force disconnect event after commit.
     *
     * @param int $loginHistoryId
     * @param string $reason
     * @return void
     * @throws LoginHistoryNotFoundException
     */
    public function execute(int $loginHistoryId, string $reason = 'FORCE_LOGOUT'): void
    {
        DB::transaction(function () use ($loginHistoryId, $reason) {
            $loginHistory = $this->userRepository->findLoginHistory($loginHistoryId);

            if (!$loginHistory) {
                throw LoginHistoryNotFoundException::forId($loginHistoryId);
            }

            $userId = (int) $loginHistory->user_id;

            $this->userRepository->clearSession($userId);
            $this->userRepository->recordLogout($loginHistoryId, $reason);

            DB::afterCommit(function () use ($userId, $reason) {
                event(new ForceDisconnectEvent(
                    userId: $userId,
                    reason: $reason,
                    timestamp: now()->toIso8601String()
                ));
            });
        });
    }
}
