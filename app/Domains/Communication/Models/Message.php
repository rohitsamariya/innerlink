<?php

namespace App\Domains\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Domains\Identity\Models\User;

class Message extends Model
{
    public $timestamps = false;

    protected $fillable = ['group_id', 'sender_id', 'message_text', 'sent_at'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function group() { return $this->belongsTo(Group::class); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function reads() { return $this->hasMany(MessageRead::class); }

    public function scopeChronological(Builder $query) { return $query->orderBy('sent_at', 'asc'); }
    public function scopeSearchText(Builder $query, string $text) { 
        return $query->whereRaw("to_tsvector('simple', coalesce(message_text, '')) @@ plainto_tsquery('simple', ?)", [$text]);
    }
}
