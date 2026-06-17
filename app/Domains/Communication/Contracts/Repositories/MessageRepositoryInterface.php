<?php

declare(strict_types=1);

namespace App\Domains\Communication\Contracts\Repositories;

use App\Domains\Communication\DTOs\MessageData;

/**
 * Interface MessageRepositoryInterface
 *
 * Contract for Message persistence operations in the Communication Domain.
 * Fully supports temporal visibility architecture.
 */
interface MessageRepositoryInterface
{
    /**
     * Create a new message in a group.
     *
     * @param MessageData $data
     * @return object
     */
    public function create(MessageData $data): object;

    /**
     * Find a message by its unique identifier.
     *
     * @param int $id
     * @return object|null
     */
    public function findById(int $id): ?object;

    /**
     * Retrieve messages for a specific group, accounting for temporal visibility rules.
     *
     * @param int $groupId
     * @param int $viewerId
     * @param string|null $since ISO-8601 timestamp or null
     * @return iterable<object>
     */
    public function getGroupMessages(int $groupId, int $viewerId, ?string $since = null): iterable;

    /**
     * Search messages within a group, accounting for temporal visibility rules.
     *
     * @param int $groupId
     * @param int $viewerId
     * @param string $query
     * @return iterable<object>
     */
    public function searchMessages(int $groupId, int $viewerId, string $query): iterable;

    /**
     * Mark a specific message as read by a user.
     *
     * @param int $messageId
     * @param int $userId
     * @return void
     */
    public function markAsRead(int $messageId, int $userId): void;

    /**
     * Retrieve all readers for a specific message.
     *
     * @param int $messageId
     * @return iterable<object>
     */
    public function getReaders(int $messageId): iterable;
}
