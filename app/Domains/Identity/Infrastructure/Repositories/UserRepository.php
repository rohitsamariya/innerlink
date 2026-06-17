<?php

declare(strict_types=1);

namespace App\Domains\Identity\Infrastructure\Repositories;

use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\DTOs\UserRegistrationData;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\UserStatusPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?object
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?object
    {
        return User::where('email', $email)->first();
    }

    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function create(UserRegistrationData $data): object
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data->fullName,
                'email' => $data->email->getValue(),
                'password' => Hash::make($data->clearPassword),
                'role' => $data->role,
                'is_enabled' => true,
            ]);

            UserStatusPeriod::create([
                'user_id' => $user->id,
                'status' => UserStatus::ENABLED,
                'start_time' => now(),
            ]);

            return $user;
        });
    }

    public function updateSession(int $userId, string $sessionId): void
    {
        User::where('id', $userId)->update(['current_session_id' => $sessionId]);
    }

    public function clearSession(int $userId): void
    {
        User::where('id', $userId)->update(['current_session_id' => null]);
    }

    public function updateLastSeen(int $userId): void
    {
        User::where('id', $userId)->update(['last_seen_at' => now()]);
    }

    public function disableUser(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            User::where('id', $userId)->update(['is_enabled' => false]);

            UserStatusPeriod::where('user_id', $userId)
                ->where('status', UserStatus::ENABLED->value)
                ->whereNull('end_time')
                ->update(['end_time' => now()]);

            UserStatusPeriod::create([
                'user_id' => $userId,
                'status' => UserStatus::DISABLED,
                'start_time' => now(),
            ]);
        });
    }

    public function enableUser(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            User::where('id', $userId)->update(['is_enabled' => true]);

            UserStatusPeriod::where('user_id', $userId)
                ->where('status', UserStatus::DISABLED->value)
                ->whereNull('end_time')
                ->update(['end_time' => now()]);

            UserStatusPeriod::create([
                'user_id' => $userId,
                'status' => UserStatus::ENABLED,
                'start_time' => now(),
            ]);
        });
    }

    public function muteUser(int $userId): void
    {
        User::where('id', $userId)->update(['is_muted' => true]);
    }

    public function unmuteUser(int $userId): void
    {
        User::where('id', $userId)->update(['is_muted' => false]);
    }

    public function getActiveUsers(): iterable
    {
        return User::where('is_enabled', true)->get();
    }

    public function recordLogin(int $userId, string $ipAddress, string $userAgent): object
    {
        return \App\Domains\Identity\Models\LoginHistory::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'logged_in_at' => now(),
        ]);
    }

    public function recordLogout(int $loginHistoryId, string $reason): void
    {
        \App\Domains\Identity\Models\LoginHistory::where('id', $loginHistoryId)
            ->whereNull('logged_out_at')
            ->update([
                'logged_out_at' => now(),
                'logout_reason' => $reason,
            ]);
    }

    public function findLoginHistory(int $loginHistoryId): ?object
    {
        return \App\Domains\Identity\Models\LoginHistory::find($loginHistoryId);
    }

    public function countAdmins(): int
    {
        return User::where('role', \App\Domains\Identity\Enums\Role::ADMIN->value)->count();
    }

    public function findLatestActiveLoginHistory(int $userId): ?object
    {
        return \App\Domains\Identity\Models\LoginHistory::where('user_id', $userId)
            ->whereNull('logged_out_at')
            ->orderBy('logged_in_at', 'desc')
            ->first();
    }
}
