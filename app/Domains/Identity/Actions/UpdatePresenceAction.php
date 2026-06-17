<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\PresenceStatus;
use App\Domains\Identity\Events\UserPresenceChanged;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePresenceAction
{
    public function execute(int $userId, PresenceStatus $status): void
    {
        $affected = DB::table('users')
            ->where('id', $userId)
            ->where('presence_status', '!=', $status->value)
            ->update(['presence_status' => $status->value]);

        if ($affected === 0) {
            return;
        }

        DB::afterCommit(fn () => UserPresenceChanged::dispatch(
            userId: $userId,
            status: $status,
        ));
    }
}
