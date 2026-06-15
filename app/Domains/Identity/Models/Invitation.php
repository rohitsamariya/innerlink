<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Invitation extends Model
{
    protected $fillable = ['email', 'role', 'token', 'status', 'invited_by', 'expires_at', 'used_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function invitedBy() { return $this->belongsTo(User::class, 'invited_by'); }

    public function scopePending(Builder $query) { return $query->where('status', 'PENDING'); }
    public function scopeExpired(Builder $query) { return $query->where('expires_at', '<', now()); }
}
