<?php

declare(strict_types=1);

namespace App\Domains\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use DateTimeInterface;
use BackedEnum;

class ExportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $id = isset($this->id) ? $this->id : null;
        $adminId = isset($this->admin_id) ? $this->admin_id : (isset($this->adminId) ? $this->adminId : null);
        $format = isset($this->format) ? $this->format : null;
        $status = isset($this->status) ? $this->status : null;
        $expiresAt = isset($this->expires_at) ? $this->expires_at : (isset($this->expiresAt) ? $this->expiresAt : null);
        $createdAt = isset($this->created_at) ? $this->created_at : (isset($this->createdAt) ? $this->createdAt : null);

        return [
            'id' => $id,
            'admin_id' => $adminId,
            'format' => $format instanceof BackedEnum ? $format->value : $format,
            'status' => $status instanceof BackedEnum ? $status->value : $status,
            'expires_at' => $expiresAt
                ? ($expiresAt instanceof DateTimeInterface ? $expiresAt->format(DateTimeInterface::ATOM) : (is_string($expiresAt) ? $expiresAt : null))
                : null,
            'created_at' => $createdAt
                ? ($createdAt instanceof DateTimeInterface ? $createdAt->format(DateTimeInterface::ATOM) : (is_string($createdAt) ? $createdAt : null))
                : null,
        ];
    }
}
