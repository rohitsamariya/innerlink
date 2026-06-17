<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;

use App\Domains\Communication\Exceptions\ImmutableRecordException;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'messages';

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'sender_id',
        'message_text',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function readers(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }

    public function delete()
    {
        throw new ImmutableRecordException('Messages are append-only and cannot be deleted.');
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new ImmutableRecordException('Messages are append-only and cannot be updated.');
    }
}
