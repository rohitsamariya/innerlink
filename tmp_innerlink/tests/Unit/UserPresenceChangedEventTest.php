<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Identity\Enums\PresenceStatus;
use App\Domains\Identity\Events\UserPresenceChanged;
use PHPUnit\Framework\TestCase;

class UserPresenceChangedEventTest extends TestCase
{
    public function test_broadcast_channel(): void
    {
        $event = new UserPresenceChanged(userId: 42, status: PresenceStatus::ONLINE);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-users.42', $channels[0]->name);
    }

    public function test_broadcast_name(): void
    {
        $event = new UserPresenceChanged(userId: 42, status: PresenceStatus::ONLINE);

        $this->assertSame('user.presence.changed', $event->broadcastAs());
    }

    public function test_online_presence_contains_user_id_and_status(): void
    {
        $event = new UserPresenceChanged(userId: 99, status: PresenceStatus::ONLINE);

        $this->assertSame(99, $event->userId);
        $this->assertSame(PresenceStatus::ONLINE, $event->status);
    }

    public function test_offline_presence_contains_user_id_and_status(): void
    {
        $event = new UserPresenceChanged(userId: 99, status: PresenceStatus::OFFLINE);

        $this->assertSame(99, $event->userId);
        $this->assertSame(PresenceStatus::OFFLINE, $event->status);
    }
}
