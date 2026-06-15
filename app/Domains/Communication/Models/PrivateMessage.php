<?php

namespace App\Domains\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Domains\Identity\Models\User;

class PrivateMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['sender_id', 'receiver_id', 'message_text', 'read_at', 'sent_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }

    public function scopeConversation(Builder $query, $userA, $userB) {
        return $query->where(function($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('receiver_id', $userB);
        })->orWhere(function($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('receiver_id', $userA);
        });
    }

    public function scopeUnread(Builder $query) { return $query->whereNull('read_at'); }
}
