<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\GroupMembership;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\MessageRead;
use App\Domains\Communication\Models\PrivateMessage;
use App\Domains\Admin\Models\AdminAuditLog;
use App\Domains\Admin\Models\ExportRequest;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'full_name', 'email', 'password', 'role', 'is_enabled', 'is_muted',
        'two_factor_secret', 'two_factor_confirmed_at', 'current_session_id', 'last_seen_at'
    ];

    protected $hidden = [
        'password', 'two_factor_secret', 'current_session_id'
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_enabled' => 'boolean',
            'is_muted' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function groups() { return $this->hasMany(Group::class, 'created_by'); }
    public function memberships() { return $this->hasMany(GroupMembership::class); }
    public function messages() { return $this->hasMany(Message::class, 'sender_id'); }
    public function privateMessagesSent() { return $this->hasMany(PrivateMessage::class, 'sender_id'); }
    public function privateMessagesReceived() { return $this->hasMany(PrivateMessage::class, 'receiver_id'); }
    public function messageReads() { return $this->hasMany(MessageRead::class); }
    public function invitations() { return $this->hasMany(Invitation::class, 'invited_by'); }
    public function loginHistories() { return $this->hasMany(LoginHistory::class); }
    public function statusPeriods() { return $this->hasMany(UserStatusPeriod::class); }
    public function auditLogs() { return $this->hasMany(AdminAuditLog::class, 'admin_id'); }
    public function exportRequests() { return $this->hasMany(ExportRequest::class, 'admin_id'); }

    public function scopeActive(Builder $query) { return $query->where('is_enabled', true); }
    public function scopeAdmins(Builder $query) { return $query->where('role', 'ADMIN'); }
    public function scopeOnline(Builder $query) { return $query->where('last_seen_at', '>=', now()->subMinutes(5)); }
}
