<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Domains\Communication\Actions\MarkMessageDeliveredAction;
use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Communication\Events\MessageDelivered;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Mockery;

class MessageDeliveredActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deliver_success_dispatches_event(): void
    {
        Event::fake();

        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $messageRepo = Mockery::mock(MessageRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $messageMock = (object)[
            'id' => 789,
            'group_id' => 1,
            'sender_id' => 456,
            'message_text' => 'Hello world',
        ];

        $userMock = (object)[
            'id' => 456,
            'full_name' => 'Alice Smith',
        ];

        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(true);

        $messageRepo->shouldReceive('findById')
            ->once()
            ->with(789)
            ->andReturn($messageMock);

        $userRepo->shouldReceive('findById')
            ->once()
            ->with(456)
            ->andReturn($userMock);

        $action = new MarkMessageDeliveredAction($messageRepo, $membershipRepo, $userRepo);
        $action->execute(messageId: 789, groupId: 1, userId: 456);

        Event::assertDispatched(MessageDelivered::class, function ($event) {
            return $event->messageId === 789
                && $event->groupId === 1
                && $event->userId === 456
                && $event->userName === 'Alice Smith';
        });
    }

    public function test_deliver_non_member_throws_exception(): void
    {
        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $messageRepo = Mockery::mock(MessageRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User is not an active member of this group');

        $action = new MarkMessageDeliveredAction($messageRepo, $membershipRepo, $userRepo);
        $action->execute(messageId: 789, groupId: 1, userId: 456);
    }

    public function test_deliver_message_not_found_throws_exception(): void
    {
        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $messageRepo = Mockery::mock(MessageRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(true);

        $messageRepo->shouldReceive('findById')
            ->once()
            ->with(789)
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message not found in this group');

        $action = new MarkMessageDeliveredAction($messageRepo, $membershipRepo, $userRepo);
        $action->execute(messageId: 789, groupId: 1, userId: 456);
    }

    public function test_deliver_message_wrong_group_throws_exception(): void
    {
        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $messageRepo = Mockery::mock(MessageRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $messageMock = (object)[
            'id' => 789,
            'group_id' => 2,
            'sender_id' => 456,
        ];

        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(true);

        $messageRepo->shouldReceive('findById')
            ->once()
            ->with(789)
            ->andReturn($messageMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Message not found in this group');

        $action = new MarkMessageDeliveredAction($messageRepo, $membershipRepo, $userRepo);
        $action->execute(messageId: 789, groupId: 1, userId: 456);
    }

    public function test_deliver_user_not_found_throws_exception(): void
    {
        $membershipRepo = Mockery::mock(GroupMembershipRepositoryInterface::class);
        $messageRepo = Mockery::mock(MessageRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $messageMock = (object)[
            'id' => 789,
            'group_id' => 1,
            'sender_id' => 456,
        ];

        $membershipRepo->shouldReceive('isUserActiveMemberOfGroup')
            ->once()
            ->with(456, 1)
            ->andReturn(true);

        $messageRepo->shouldReceive('findById')
            ->once()
            ->with(789)
            ->andReturn($messageMock);

        $userRepo->shouldReceive('findById')
            ->once()
            ->with(456)
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $action = new MarkMessageDeliveredAction($messageRepo, $membershipRepo, $userRepo);
        $action->execute(messageId: 789, groupId: 1, userId: 456);
    }
}
