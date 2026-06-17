<?php

declare(strict_types=1);

namespace App\Domains\Identity\Listeners;

use App\Domains\Identity\Actions\UpdatePresenceAction;
use App\Domains\Identity\Enums\PresenceStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Reverb\Events\ChannelCreated;
use Laravel\Reverb\Events\ChannelRemoved;
use Throwable;

class UpdateUserPresence
{
    private const CHANNEL_PATTERN = '/^private-users\.(\d+)$/';

    public function __construct(
        private readonly UpdatePresenceAction $action,
    ) {}

    public function handle(ChannelCreated|ChannelRemoved $event): void
    {
        try {
            $channelName = $event->channel->name();

            $userId = Str::match(self::CHANNEL_PATTERN, $channelName);

            if ($userId === null) {
                return;
            }

            $status = match (true) {
                $event instanceof ChannelCreated => PresenceStatus::ONLINE,
                $event instanceof ChannelRemoved => PresenceStatus::OFFLINE,
            };

            $this->action->execute((int) $userId, $status);
        } catch (Throwable $e) {
            Log::error('Presence update failed', [
                'channel' => $event->channel->name(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
