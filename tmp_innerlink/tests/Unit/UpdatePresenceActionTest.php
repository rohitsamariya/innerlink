<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Identity\Actions\UpdatePresenceAction;
use App\Domains\Identity\Enums\PresenceStatus;
use App\Domains\Identity\Events\UserPresenceChanged;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpdatePresenceActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 20)->default('EMPLOYEE');
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

    public function test_updates_from_offline_to_online(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'hashed',
            'presence_status' => 'OFFLINE',
        ]);

        $action = new UpdatePresenceAction();
        $action->execute($user->id, PresenceStatus::ONLINE);

        $this->assertSame(PresenceStatus::ONLINE, $user->fresh()->presence_status);

        Event::assertDispatched(UserPresenceChanged::class, function ($event) use ($user) {
            return $event->userId === $user->id && $event->status === PresenceStatus::ONLINE;
        });
    }

    public function test_updates_from_online_to_offline(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'hashed',
            'presence_status' => 'ONLINE',
        ]);

        $action = new UpdatePresenceAction();
        $action->execute($user->id, PresenceStatus::OFFLINE);

        $this->assertSame(PresenceStatus::OFFLINE, $user->fresh()->presence_status);

        Event::assertDispatched(UserPresenceChanged::class, function ($event) use ($user) {
            return $event->userId === $user->id && $event->status === PresenceStatus::OFFLINE;
        });
    }

    public function test_skips_update_when_already_online(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Charlie',
            'email' => 'charlie@example.com',
            'password' => 'hashed',
            'presence_status' => 'ONLINE',
        ]);

        $action = new UpdatePresenceAction();
        $action->execute($user->id, PresenceStatus::ONLINE);

        Event::assertNotDispatched(UserPresenceChanged::class);
    }

    public function test_skips_update_when_already_offline(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Diana',
            'email' => 'diana@example.com',
            'password' => 'hashed',
            'presence_status' => 'OFFLINE',
        ]);

        $action = new UpdatePresenceAction();
        $action->execute($user->id, PresenceStatus::OFFLINE);

        Event::assertNotDispatched(UserPresenceChanged::class);
    }

    public function test_does_not_update_updated_at(): void
    {
        Event::fake();

        $user = User::create([
            'full_name' => 'Eve',
            'email' => 'eve@example.com',
            'password' => 'hashed',
            'presence_status' => 'OFFLINE',
        ]);

        $originalUpdatedAt = $user->fresh()->updated_at;

        $action = new UpdatePresenceAction();
        $action->execute($user->id, PresenceStatus::ONLINE);

        $this->assertSame(
            $originalUpdatedAt->toISOString(),
            $user->fresh()->updated_at->toISOString()
        );
    }

    public function test_handles_non_existent_user_gracefully(): void
    {
        Event::fake();

        $action = new UpdatePresenceAction();
        $action->execute(99999, PresenceStatus::ONLINE);

        Event::assertNotDispatched(UserPresenceChanged::class);
    }
}
