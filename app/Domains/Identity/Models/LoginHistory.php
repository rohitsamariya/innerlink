<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'ip_address', 'user_agent', 'logged_in_at', 'logged_out_at', 'logout_reason'];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function scopeActiveSessions(Builder $query) { return $query->whereNull('logged_out_at'); }

    // Enforce Immutability (Delete Only)
    public function delete()
    {
        throw new Exception('LoginHistory is an append-mostly immutable table. Deletions are strictly prohibited.');
    }
}
