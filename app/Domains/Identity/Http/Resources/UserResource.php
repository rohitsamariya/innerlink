<?php

declare(strict_types=1);

namespace App\Domains\Identity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use DateTimeInterface;
use BackedEnum;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $id = isset($this->id) ? $this->id : (isset($this->userId) ? $this->userId : null);
        $fullName = isset($this->full_name) ? $this->full_name : (isset($this->fullName) ? $this->fullName : null);
        $role = isset($this->role) ? $this->role : null;
        $isEnabled = isset($this->is_enabled) ? $this->is_enabled : (isset($this->isEnabled) ? $this->isEnabled : null);
        $isMuted = isset($this->is_muted) ? $this->is_muted : (isset($this->isMuted) ? $this->isMuted : null);
        $lastSeenAt = isset($this->last_seen_at) ? $this->last_seen_at : (isset($this->lastSeenAt) ? $this->lastSeenAt : null);
        $presenceStatus = $this->presence_status ?? null;

        return [
            'id' => $id,
            'full_name' => $fullName,
            'email' => isset($this->email) ? $this->email : null,
            'role' => $role instanceof BackedEnum ? $role->value : $role,
            'is_enabled' => $isEnabled !== null ? (bool) $isEnabled : null,
            'is_muted' => $isMuted !== null ? (bool) $isMuted : null,
            'presence_status' => $presenceStatus instanceof BackedEnum ? $presenceStatus->value : $presenceStatus,
            'last_seen_at' => $lastSeenAt
                ? ($lastSeenAt instanceof DateTimeInterface ? $lastSeenAt->format(DateTimeInterface::ATOM) : (is_string($lastSeenAt) ? $lastSeenAt : null))
                : null,
        ];
    }
}
