<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Controllers;

use App\Domains\Communication\Actions\CreateGroupAction;
use App\Domains\Communication\Http\Requests\StoreGroupRequest;
use App\Domains\Communication\Http\Resources\GroupResource;
use App\Domains\Communication\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GroupController
{
    public function __construct(
        private readonly CreateGroupAction $createGroupAction
    ) {}

    public function index(): JsonResponse
    {
        $userId = request()->user()->id;

        $groups = Group::whereHas('memberships', function ($query) use ($userId) {
            $query->where('user_id', $userId)->whereNull('left_at');
        })->get();

        $groups->loadCount(['messages as unread_count' => function ($query) use ($userId) {
            $query->where('sender_id', '!=', $userId)
                  ->whereDoesntHave('readers', function ($q) use ($userId) {
                      $q->where('user_id', $userId);
                  });
        }]);

        return GroupResource::collection($groups)->response();
    }

    public function store(StoreGroupRequest $request): JsonResponse
    {
        $group = $this->createGroupAction->execute(
            name: $request->input('name'),
            createdBy: $request->user()->id,
        );

        return (new GroupResource($group))->response();
    }

    public function show(Group $group): JsonResponse
    {
        Gate::authorize('view', $group);

        return (new GroupResource($group))->response();
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        Gate::authorize('view', $group);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'is_enabled' => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) {
            $group->name = $validated['name'];
        }

        if (isset($validated['is_enabled'])) {
            $group->is_enabled = $validated['is_enabled'];
        }

        $group->save();

        return (new GroupResource($group))->response();
    }
}
