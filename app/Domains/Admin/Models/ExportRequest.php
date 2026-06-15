<?php

namespace App\Domains\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Identity\Models\User;

class ExportRequest extends Model
{
    protected $fillable = ['admin_id', 'filters', 'format', 'status', 'file_path', 'error_message', 'expires_at'];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
