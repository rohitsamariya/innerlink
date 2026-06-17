<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;


use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMessage extends Model
{
    protected $table = 'private_messages';

    const UPDATED_AT = null;
    const CREATED_AT = 'sent_at';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message_text',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
