<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class GroupMembership extends Model
{
    protected $table = 'group_memberships';

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'user_id',
        'added_by',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }
}
