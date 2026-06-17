<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\GroupMembership;
use App\Domains\Communication\Models\Message;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

class MessageDeliveredEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('member');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_muted')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('current_session_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('groups', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestampsTz();
            $table->unique('name');
        });

        Schema::create('group_memberships', function ($table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups', 'id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('added_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();
        });

        Schema::create('messages', function ($table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups', 'id')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users', 'id')->onDelete('restrict');
            $table->text('message_text');
            $table->timestampTz('sent_at')->useCurrent();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_authenticated_member_can_deliver_message(): void
    {
        $user = User::create([
            'full_name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
            'is_enabled' => true,
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'created_by' => $user->id,
        ]);

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'added_by' => $user->id,
        ]);

        $message = Message::create([
            'group_id' => $group->id,
            'sender_id' => $user->id,
            'message_text' => 'Hello world',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/groups/{$group->id}/messages/{$message->id}/deliver");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'delivered']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/groups/1/messages/1/deliver');

        $response->assertStatus(401);
    }

    public function test_non_member_returns_403(): void
    {
        $user = User::create([
            'full_name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
            'is_enabled' => true,
        ]);

        $otherUser = User::create([
            'full_name' => 'Carol',
            'email' => 'carol@example.com',
            'password' => Hash::make('password'),
            'is_enabled' => true,
        ]);

        $group = Group::create([
            'name' => 'Private Group',
            'created_by' => $otherUser->id,
        ]);

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $otherUser->id,
            'added_by' => $otherUser->id,
        ]);

        $message = Message::create([
            'group_id' => $group->id,
            'sender_id' => $otherUser->id,
            'message_text' => 'Secret message',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/groups/{$group->id}/messages/{$message->id}/deliver");

        $response->assertStatus(403);
    }

    public function test_deliver_non_existent_message_returns_404(): void
    {
        $user = User::create([
            'full_name' => 'Alice Smith',
            'email' => 'alice2@example.com',
            'password' => Hash::make('password'),
            'is_enabled' => true,
        ]);

        $group = Group::create([
            'name' => 'Test Group 2',
            'created_by' => $user->id,
        ]);

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'added_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/groups/{$group->id}/messages/99999/deliver");

        $response->assertStatus(404);
    }
}
