<?php

namespace App\Domains\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Domains\Identity\Models\User;

class GroupMembership extends Model
{
    public $timestamps = false;

    protected $fillable = ['group_id', 'user_id', 'added_by', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function group() { return $this->belongsTo(Group::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function addedBy() { return $this->belongsTo(User::class, 'added_by'); }

    public function scopeActive(Builder $query) { return $query->whereNull('left_at'); }
    public function scopeHistorical(Builder $query) { return $query->whereNotNull('left_at'); }
}
