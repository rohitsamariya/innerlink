<?php

declare(strict_types=1);

namespace App\Domains\Identity\Models;

use App\Domains\Identity\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
        'is_enabled',
        'is_muted',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'current_session_id',
        'last_seen_at',
        'presence_status',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'is_enabled' => 'boolean',
            'is_muted' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'presence_status' => \App\Domains\Identity\Enums\PresenceStatus::class,
        ];
    }

    public function statusPeriods(): HasMany
    {
        return $this->hasMany(UserStatusPeriod::class, 'user_id');
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'user_id');
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(\App\Domains\Communication\Models\GroupMembership::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(\App\Domains\Communication\Models\Message::class, 'sender_id');
    }

    public function messageReads(): HasMany
    {
        return $this->hasMany(\App\Domains\Communication\Models\MessageRead::class, 'user_id');
    }

    public function sentPrivateMessages(): HasMany
    {
        return $this->hasMany(\App\Domains\Communication\Models\PrivateMessage::class, 'sender_id');
    }

    public function receivedPrivateMessages(): HasMany
    {
        return $this->hasMany(\App\Domains\Communication\Models\PrivateMessage::class, 'receiver_id');
    }

    public function adminAuditLogs(): HasMany
    {
        return $this->hasMany(\App\Domains\Admin\Models\AdminAuditLog::class, 'admin_id');
    }

    public function exportRequests(): HasMany
    {
        return $this->hasMany(\App\Domains\Admin\Models\ExportRequest::class, 'admin_id');
    }

    public function initiatedCalls(): HasMany
    {
        return $this->hasMany(\App\Domains\Calling\Models\Call::class, 'caller_id');
    }

    public function receivedCalls(): HasMany
    {
        return $this->hasMany(\App\Domains\Calling\Models\Call::class, 'receiver_id');
    }
}
