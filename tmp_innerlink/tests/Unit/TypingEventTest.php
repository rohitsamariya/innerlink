<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Domains\Communication\Events\UserTypingStarted;
use App\Domains\Communication\Events\UserTypingStopped;

class TypingEventTest extends TestCase
{
    public function test_typing_started_broadcast_channel(): void
    {
        $event = new UserTypingStarted(userId: 123, userName: 'Alice', groupId: 1);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-groups.1', $channels[0]->name);
    }

    public function test_typing_started_broadcast_name(): void
    {
        $event = new UserTypingStarted(userId: 123, userName: 'Alice', groupId: 1);

        $this->assertSame('typing.started', $event->broadcastAs());
    }

    public function test_typing_stopped_broadcast_channel(): void
    {
        $event = new UserTypingStopped(userId: 123, groupId: 1);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-groups.1', $channels[0]->name);
    }

    public function test_typing_stopped_broadcast_name(): void
    {
        $event = new UserTypingStopped(userId: 123, groupId: 1);

        $this->assertSame('typing.stopped', $event->broadcastAs());
    }
}
