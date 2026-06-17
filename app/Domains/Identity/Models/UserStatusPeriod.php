<?php

declare(strict_types=1);

namespace App\Domains\Identity\Models;

use App\Domains\Identity\Enums\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatusPeriod extends Model
{
    protected $table = 'user_status_periods';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'status',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
