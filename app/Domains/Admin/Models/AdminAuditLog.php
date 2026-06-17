<?php

declare(strict_types=1);

namespace App\Domains\Admin\Models;

use App\Domains\Admin\Exceptions\AdminViolationException;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function delete()
    {
        throw new AdminViolationException('Admin audit logs are append-only and cannot be deleted.');
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new AdminViolationException('Admin audit logs are append-only and cannot be updated.');
    }
}
