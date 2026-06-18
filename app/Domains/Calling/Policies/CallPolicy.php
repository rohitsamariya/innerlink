<?php

declare(strict_types=1);

namespace App\Domains\Calling\Policies;

use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\Models\User;

class CallPolicy
{
    public function initiate(User $caller, int $receiverId): bool
    {
        if ($caller->role === Role::ADMIN) {
            return true;
        }

        $receiver = User::query()->find($receiverId);

        return $receiver && $receiver->role === Role::ADMIN;
    }
}
