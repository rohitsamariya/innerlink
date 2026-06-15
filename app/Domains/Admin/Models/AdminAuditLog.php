<?php

namespace App\Domains\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Identity\Models\User;
use Exception;

class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_id', 'action', 'target_type', 'target_id', 'payload', 'ip_address', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }

    // Enforce Immutability
    public function update(array $attributes = [], array $options = [])
    {
        throw new Exception('AdminAuditLog is an append-only immutable table.');
    }

    public function delete()
    {
        throw new Exception('AdminAuditLog is an append-only immutable table.');
    }
}
