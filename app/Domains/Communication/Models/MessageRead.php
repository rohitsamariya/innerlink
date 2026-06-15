<?php

namespace App\Domains\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Identity\Models\User;

class MessageRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['message_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function message() { return $this->belongsTo(Message::class); }
    public function user() { return $this->belongsTo(User::class); }
}
