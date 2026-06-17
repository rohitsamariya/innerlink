<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domains\Identity\Actions\AuthenticateUserAction;
use App\Domains\Identity\Actions\RevokeUserSessionAction;
use App\Domains\Communication\Actions\DispatchMessageAction;
use App\Domains\Admin\Actions\RequestExportAction;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Admin\Contracts\Repositories\ExportRepositoryInterface;
use App\Domains\Identity\Exceptions\AuthenticationFailedException;
use App\Domains\Communication\DTOs\MessageData;
use App\Domains\Communication\ValueObjects\MessageContent;
use App\Domains\Admin\DTOs\ExportConfigData;
use App\Domains\Admin\Enums\ExportFormat;
use App\Domains\Admin\Enums\ExportStatus;
use App\Domains\Identity\Events\ForceDisconnectEvent;
use App\Domains\Communication\Events\MessageSent;
use App\Domains\Admin\Jobs\ProcessExportJob;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Hash;
use Mockery;

class DomainActionsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticate_user_action_success(): void
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userMock = (object)[
            'id' => 123,
            'password' => Hash::make('secret-password-123'),
            'is_enabled' => true,
        ];

        $loginHistoryMock = (object)[
            'logged_in_at' => now(),
        ];

        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('user@example.com')
            ->andReturn($userMock);

        $userRepo->shouldReceive('recordLogin')
            ->once()
            ->with(123, '127.0.0.1', 'Mozilla/5.0')
            ->andReturn($loginHistoryMock);

        $action = new AuthenticateUserAction($userRepo);
        $session = $action->execute('user@example.com', 'secret-password-123', '127.0.0.1', 'Mozilla/5.0');

        $this->assertEquals(123, $session->userId);
        $this->assertEquals('127.0.0.1', $session->ipAddress);
        $this->assertEquals('Mozilla/5.0', $session->userAgent);
    }

    public function test_authenticate_user_action_invalid_password(): void
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userMock = (object)[
            'id' => 123,
            'password' => Hash::make('secret-password-123'),
            'is_enabled' => true,
        ];

        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('user@example.com')
            ->andReturn($userMock);

        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('Invalid email or password.');

        $action = new AuthenticateUserAction($userRepo);
        $action->execute('user@example.com', 'wrong-password', '127.0.0.1', 'Mozilla/5.0');
    }

    public function test_authenticate_user_action_disabled_user(): void
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userMock = (object)[
            'id' => 123,
            'password' => Hash::make('secret-password-123'),
            'is_enabled' => false,
        ];

        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('user@example.com')
            ->andReturn($userMock);

        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('This account has been disabled.');

        $action = new AuthenticateUserAction($userRepo);
        $action->execute('user@example.com', 'secret-password-123', '127.0.0.1', 'Mozilla/5.0');
    }

    public function test_revoke_user_session_action(): void
    {
        Event::fake();

        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        
        $loginHistoryMock = (object)[
            'id' => 1001,
            'user_id' => 123,
        ];

        $userRepo->shouldReceive('findLoginHistory')
            ->once()
            ->with(1001)
            ->andReturn($loginHistoryMock);

        $userRepo->shouldReceive('clearSession')
            ->once()
            ->with(123);

        $userRepo->shouldReceive('recordLogout')
            ->once()
            ->with(1001, 'FORCE_LOGOUT');

        $action = new RevokeUserSessionAction($userRepo);
        $action->execute(1001, 'FORCE_LOGOUT');

        Event::assertDispatched(ForceDisconnectEvent::class, function ($event) {
            return $event->userId === 123 && $event->reason === 'FORCE_LOGOUT';
        });
    }

    public function test_dispatch_message_action_success(): void
    {
        Event::fake();

        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $messageRepo = Mockery::mock(MessageRepositoryInterface::class);

        $senderMock = (object)[
            'id' => 456,
            'full_name' => 'John Doe',
        ];

        $messageMock = Mockery::mock(\App\Domains\Communication\Models\Message::class)->makePartial();
        $messageMock->id = 789;
        $messageMock->group_id = 1;
        $messageMock->sender_id = 456;
        $messageMock->message_text = 'Hello world';
        $messageMock->sent_at = now();

        $userRepo->shouldReceive('findById')
            ->once()
            ->with(456)
            ->andReturn($senderMock);

        $messageData = new MessageData(
            groupId: 1,
            senderId: 456,
            content: new MessageContent('Hello world')
        );

        $messageRepo->shouldReceive('create')
            ->once()
            ->with($messageData)
            ->andReturn($messageMock);

        $action = new DispatchMessageAction($messageRepo, $userRepo);
        $result = $action->execute($messageData);

        $this->assertEquals(789, $result->id);

        Event::assertDispatched(MessageSent::class, function ($event) {
            return $event->id === 789 && $event->senderName === 'John Doe';
        });
    }

    public function test_request_export_action_success(): void
    {
        Queue::fake();

        $exportRepo = Mockery::mock(ExportRepositoryInterface::class);
        
        $exportRequestMock = (object)[
            'id' => 10,
            'admin_id' => 99,
            'format' => ExportFormat::CSV,
            'status' => ExportStatus::PENDING,
            'filters' => ['user_id' => 5],
            'expires_at' => now()->addDays(1),
            'file_path' => null,
            'error_message' => null,
        ];

        $config = new ExportConfigData(
            format: ExportFormat::CSV,
            type: 'users',
            filters: ['user_id' => 5]
        );

        $exportRepo->shouldReceive('createRequest')
            ->once()
            ->with(99, $config, Mockery::type('string'))
            ->andReturn($exportRequestMock);

        $action = new RequestExportAction($exportRepo);
        $dto = $action->execute(99, $config);

        $this->assertEquals(10, $dto->id);
        $this->assertEquals(ExportFormat::CSV, $dto->format);
        $this->assertEquals(['user_id' => 5], $dto->filters);

        Queue::assertPushed(ProcessExportJob::class, function ($job) {
            return $job->exportRequestId === 10;
        });
    }
}
