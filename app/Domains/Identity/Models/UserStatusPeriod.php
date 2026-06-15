<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserStatusPeriod extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'status', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function scopeActive(Builder $query) { return $query->whereNull('end_time'); }
}
