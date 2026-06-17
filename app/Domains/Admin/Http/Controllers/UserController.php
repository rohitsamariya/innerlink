<?php

declare(strict_types=1);

namespace App\Domains\Admin\Http\Controllers;

use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\Http\Resources\UserResource;
use App\Domains\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::orderBy('id')->get());
    }

    public function toggleStatus(User $user): JsonResponse
    {
        if ($user->role === Role::ADMIN) {
            return response()->json(['message' => 'Admin users cannot be disabled.'], 422);
        }

        $user->is_enabled = !$user->is_enabled;
        $user->save();

        return response()->json([
            'message' => 'User status updated.',
            'user' => new UserResource($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255',
            'role' => 'required|string|in:USER,MANAGER',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'is_enabled' => true,
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => new UserResource($user),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->role === Role::ADMIN && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Only the admin themselves can edit their own profile.'], 422);
        }

        $rules = [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'role' => 'sometimes|string|in:USER,MANAGER,ADMIN',
            'password' => 'sometimes|string|min:6|max:255',
        ];

        $validated = $request->validate($rules);

        if (isset($validated['full_name'])) {
            $user->full_name = $validated['full_name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['role'])) {
            if ($user->role === Role::ADMIN && $validated['role'] !== 'ADMIN') {
                return response()->json(['message' => 'Cannot change admin role.'], 422);
            }
            $user->role = $validated['role'];
        }

        if (isset($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => new UserResource($user->fresh()),
        ]);
    }
}
