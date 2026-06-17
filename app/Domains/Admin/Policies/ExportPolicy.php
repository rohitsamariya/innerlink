<?php

declare(strict_types=1);

namespace App\Domains\Admin\Policies;

use App\Domains\Admin\Models\ExportRequest;
use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\Models\User;

class ExportPolicy
{
    /**
     * Determine whether the user can view the export request.
     */
    public function view(User $user, ExportRequest $export): bool
    {
        if (!$user->is_enabled || $user->role !== Role::ADMIN) {
            return false;
        }

        return $user->id === $export->admin_id;
    }
}
