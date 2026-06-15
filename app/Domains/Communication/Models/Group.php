<?php

namespace App\Domains\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Identity\Models\User;

class Group extends Model
{
    protected $fillable = ['name', 'created_by'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function memberships() { return $this->hasMany(GroupMembership::class); }
    public function messages() { return $this->hasMany(Message::class); }

    public function scopeWithActiveMembers($query) {
        return $query->with(['memberships' => function ($q) {
            $q->active();
        }]);
    }
}
