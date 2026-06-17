<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Domains\Communication\Actions\MarkUserTypingAction;
use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\Events\UserTypingStarted;
use App\Domains\Communication\Events\UserTypingStopped;
use Illuminate\Support\Facades\Event;
use Mockery;

class TypingActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_started_dispatches_user_typing_started(): void
    {
        Event::fake();

        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(true);

        $action = new MarkUserTypingAction($membershipRepo);
        $action->execute(groupId: 1, userId: 456, userName: 'Alice', action: 'started');

        Event::assertDispatched(UserTypingStarted::class, function ($event) {
            return $event->userId === 456
                && $event->userName === 'Alice'
                && $event->groupId === 1;
        });
    }

    public function test_stopped_dispatches_user_typing_stopped(): void
    {
        Event::fake();

        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(true);

        $action = new MarkUserTypingAction($membershipRepo);
        $action->execute(groupId: 1, userId: 456, userName: 'Alice', action: 'stopped');

        Event::assertDispatched(UserTypingStopped::class, function ($event) {
            return $event->userId === 456 && $event->groupId === 1;
        });

        Event::assertNotDispatched(UserTypingStarted::class);
    }

    public function test_invalid_action_throws_exception(): void
    {
        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid typing action "invalid".');

        $action = new MarkUserTypingAction($membershipRepo);
        $action->execute(groupId: 1, userId: 456, userName: 'Alice', action: 'invalid');
    }

    public function test_non_member_throws_exception(): void
    {
        Event::fake();

        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(false);

        $this->expectException(\App\Domains\Communication\Exceptions\NotGroupMemberException::class);
        $this->expectExceptionMessage('User 456 is not an active member of group 1.');

        $action = new MarkUserTypingAction($membershipRepo);
        $action->execute(groupId: 1, userId: 456, userName: 'Alice', action: 'started');
    }
}
