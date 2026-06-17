<?php

declare(strict_types=1);

namespace App\Domains\Communication\Actions;

use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Communication\DTOs\MessageData;
use App\Domains\Communication\Events\MessageSent;
use App\Domains\Identity\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class DispatchMessageAction
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Persist message and dispatch MessageSent event only after successful commit.
     *
     * @param MessageData $data
     * @return object The persisted message model
     * @throws UserNotFoundException if sender user does not exist
     */
    public function execute(MessageData $data): object
    {
        return DB::transaction(function () use ($data) {
            $sender = $this->userRepository->findById($data->senderId);
            if (!$sender) {
                throw UserNotFoundException::forId($data->senderId);
            }

            $message = $this->messageRepository->create($data);

            DB::afterCommit(function () use ($message, $sender) {
                $sentAt = $message->sent_at;
                $sentAtStr = ($sentAt instanceof \DateTimeInterface)
                    ? $sentAt->format(\DateTimeInterface::ATOM)
                    : (is_string($sentAt) ? $sentAt : now()->toIso8601String());

                try {
                    event(new MessageSent(
                        id: $message->id,
                        groupId: $message->group_id,
                        senderId: $message->sender_id,
                        senderName: $sender->full_name,
                        messageText: $message->message_text,
                        sentAt: $sentAtStr
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed for message {id}: {error}', [
                        'id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return $message;
        });
    }
}
