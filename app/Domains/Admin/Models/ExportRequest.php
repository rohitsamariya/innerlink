<?php

declare(strict_types=1);

namespace App\Domains\Admin\Models;

use App\Domains\Admin\Enums\ExportFormat;
use App\Domains\Admin\Enums\ExportStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportRequest extends Model
{
    protected $table = 'export_requests';

    protected $fillable = [
        'admin_id',
        'filters',
        'format',
        'status',
        'file_path',
        'error_message',
        'internal_error_details',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'format' => ExportFormat::class,
            'status' => ExportStatus::class,
            'internal_error_details' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
