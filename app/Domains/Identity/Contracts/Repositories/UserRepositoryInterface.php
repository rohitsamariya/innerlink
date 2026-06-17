<?php

declare(strict_types=1);

namespace App\Domains\Identity\Contracts\Repositories;

use App\Domains\Identity\DTOs\UserRegistrationData;

/**
 * Interface UserRepositoryInterface
 *
 * Contract for User persistence operations in the Identity Domain.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by their unique identifier.
     *
     * @param int $id
     * @return object|null
     */
    public function findById(int $id): ?object;

    /**
     * Find a user by their email address.
     *
     * @param string $email
     * @return object|null
     */
    public function findByEmail(string $email): ?object;

    /**
     * Check if a user exists with the given email address.
     *
     * @param string $email
     * @return bool
     */
    public function existsByEmail(string $email): bool;

    /**
     * Create a new user.
     *
     * @param UserRegistrationData $data
     * @return object
     */
    public function create(UserRegistrationData $data): object;

    /**
     * Update the active session identifier for the user.
     *
     * @param int $userId
     * @param string $sessionId
     * @return void
     */
    public function updateSession(int $userId, string $sessionId): void;

    /**
     * Clear the active session identifier for the user.
     *
     * @param int $userId
     * @return void
     */
    public function clearSession(int $userId): void;

    /**
     * Update the last seen timestamp for the user.
     *
     * @param int $userId
     * @return void
     */
    public function updateLastSeen(int $userId): void;

    /**
     * Disable a user's account.
     *
     * @param int $userId
     * @return void
     */
    public function disableUser(int $userId): void;

    /**
     * Enable a user's account.
     *
     * @param int $userId
     * @return void
     */
    public function enableUser(int $userId): void;

    /**
     * Mute a user.
     *
     * @param int $userId
     * @return void
     */
    public function muteUser(int $userId): void;

    /**
     * Unmute a user.
     *
     * @param int $userId
     * @return void
     */
    public function unmuteUser(int $userId): void;

    /**
     * Get a list of all active users.
     *
     * @return iterable<object>
     */
    public function getActiveUsers(): iterable;

    /**
     * Record a user login.
     *
     * @param int $userId
     * @param string $ipAddress
     * @param string $userAgent
     * @return object
     */
    public function recordLogin(int $userId, string $ipAddress, string $userAgent): object;

    /**
     * Record a user logout/revocation.
     *
     * @param int $loginHistoryId
     * @param string $reason
     * @return void
     */
    public function recordLogout(int $loginHistoryId, string $reason): void;

    /**
     * Find a login history record by its ID.
     *
     * @param int $loginHistoryId
     * @return object|null
     */
    public function findLoginHistory(int $loginHistoryId): ?object;

    /**
    * Count the number of admin users.
    *
    * @return int
    */
    public function countAdmins(): int;

    /**
     * Find the latest active login history record for a user.
     *
     * @param int $userId
     * @return object|null
     */
    public function findLatestActiveLoginHistory(int $userId): ?object;
}
