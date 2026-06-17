<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\GroupMembership;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

class TypingEndpointTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_member_sends_started_returns_200(): void
    {
        [$user, $group] = $this->createMemberWithGroup();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/groups/{$group->id}/typing", [
            'action' => 'started',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_member_sends_stopped_returns_200(): void
    {
        [$user, $group] = $this->createMemberWithGroup();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/groups/{$group->id}/typing", [
            'action' => 'stopped',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/groups/1/typing', [
            'action' => 'started',
        ]);

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

        [$otherUser, $group] = $this->createMemberWithGroup();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/groups/{$group->id}/typing", [
            'action' => 'started',
        ]);

        $response->assertStatus(403);
    }

    public function test_invalid_action_returns_422(): void
    {
        [$user, $group] = $this->createMemberWithGroup();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/groups/{$group->id}/typing", [
            'action' => 'invalid',
        ]);

        $response->assertStatus(422);
    }

    public function test_missing_action_returns_422(): void
    {
        [$user, $group] = $this->createMemberWithGroup();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/groups/{$group->id}/typing", []);

        $response->assertStatus(422);
    }

    private function createMemberWithGroup(): array
    {
        $user = User::create([
            'full_name' => 'Alice Smith',
            'email' => 'alice_' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'is_enabled' => true,
        ]);

        $group = Group::create([
            'name' => 'Test Group ' . uniqid(),
            'created_by' => $user->id,
        ]);

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'added_by' => $user->id,
        ]);

        return [$user, $group];
    }
}
