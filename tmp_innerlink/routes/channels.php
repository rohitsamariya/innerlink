<?php

use Illuminate\Support\Facades\Broadcast;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Enums\Role;
use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\DTOs\PresenceChannelData;

Broadcast::channel('users.{id}', function (User $user, int $id) {
    if (!$user->is_enabled) {
        return false;
    }

    return (int) $user->id === $id;
});

Broadcast::channel('groups.{id}', function (User $user, int $id) {
    if (!$user->is_enabled) {
        return false;
    }
    
    /** @var GroupMembershipRepositoryInterface $repo */
    $repo = app(GroupMembershipRepositoryInterface::class);
    
    return $repo->isUserActiveMemberOfGroup($user->id, $id);
});

Broadcast::channel('presence-groups.{id}', function (User $user, int $id) {
    if (!$user->is_enabled) {
        return false;
    }
    
    /** @var GroupMembershipRepositoryInterface $repo */
    $repo = app(GroupMembershipRepositoryInterface::class);
    
    if (!$repo->isUserActiveMemberOfGroup($user->id, $id)) {
        return false;
    }
    
    return (new PresenceChannelData(
        id: $user->id,
        fullName: $user->full_name,
    ))->toArray();
});

Broadcast::channel('admin.dashboard', function (User $user) {
    return $user->is_enabled && $user->role === Role::ADMIN;
});
