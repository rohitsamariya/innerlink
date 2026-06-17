<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Identity\Actions\UpdatePresenceAction;
use App\Domains\Identity\Enums\PresenceStatus;
use App\Domains\Identity\Events\UserPresenceChanged;
use App\Domains\Identity\Listeners\UpdateUserPresence;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Laravel\Reverb\Events\ChannelCreated;
use Laravel\Reverb\Events\ChannelRemoved;
use Laravel\Reverb\Protocols\Pusher\Channels\Channel;
use Mockery;
use Tests\TestCase;

class UpdateUserPresenceListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 20)->default('USER');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_muted')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('current_session_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('presence_status', 20)->default('OFFLINE');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_channel_created_sets_online(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'hashed',
        ]);

        $listener = new UpdateUserPresence(new UpdatePresenceAction());

        $channel = Mockery::mock(Channel::class);
        $channel->shouldReceive('name')->andReturn('private-users.' . $user->id);

        $listener->handle(new ChannelCreated($channel));

        $this->assertSame(PresenceStatus::ONLINE, $user->fresh()->presence_status);

        Event::assertDispatched(UserPresenceChanged::class, function ($event) use ($user) {
            return $event->userId === $user->id && $event->status === PresenceStatus::ONLINE;
        });
    }

    public function test_channel_removed_sets_offline(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'hashed',
            'presence_status' => 'ONLINE',
        ]);

        $listener = new UpdateUserPresence(new UpdatePresenceAction());

        $channel = Mockery::mock(Channel::class);
        $channel->shouldReceive('name')->andReturn('private-users.' . $user->id);

        $listener->handle(new ChannelRemoved($channel));

        $this->assertSame(PresenceStatus::OFFLINE, $user->fresh()->presence_status);

        Event::assertDispatched(UserPresenceChanged::class, function ($event) use ($user) {
            return $event->userId === $user->id && $event->status === PresenceStatus::OFFLINE;
        });
    }

    public function test_ignores_non_user_channels(): void
    {
        Event::fake();

        $listener = new UpdateUserPresence(new UpdatePresenceAction());

        $channel = Mockery::mock(Channel::class);
        $channel->shouldReceive('name')->andReturn('presence-groups.5');

        $listener->handle(new ChannelCreated($channel));

        Event::assertNotDispatched(UserPresenceChanged::class);
    }

    public function test_ignores_malformed_channel_names(): void
    {
        Event::fake();

        $listener = new UpdateUserPresence(new UpdatePresenceAction());

        $channel = Mockery::mock(Channel::class);

        $invalidNames = ['users.42', 'private-users', 'private-users.abc', 'private-users.42.extra', 'private-admin.dashboard'];

        foreach ($invalidNames as $name) {
            $channel->shouldReceive('name')->andReturn($name);
            $listener->handle(new ChannelCreated($channel));
        }

        Event::assertNotDispatched(UserPresenceChanged::class);
    }

    public function test_non_existent_user_does_not_crash(): void
    {
        Event::fake();

        $listener = new UpdateUserPresence(new UpdatePresenceAction());

        $channel = Mockery::mock(Channel::class);
        $channel->shouldReceive('name')->andReturn('private-users.99999');

        $listener->handle(new ChannelCreated($channel));

        Event::assertNotDispatched(UserPresenceChanged::class);
    }
}
