<?php

declare(strict_types=1);

namespace App\Domains\Communication\Actions;

use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\GroupMembership;
use Illuminate\Support\Facades\DB;

final readonly class CreateGroupAction
{
    public function execute(string $name, int $createdBy): Group
    {
        return DB::transaction(function () use ($name, $createdBy) {
            $group = Group::create([
                'name' => $name,
                'created_by' => $createdBy,
            ]);

            GroupMembership::create([
                'group_id' => $group->id,
                'user_id' => $createdBy,
                'added_by' => $createdBy,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }
}
