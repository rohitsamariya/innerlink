<?php

declare(strict_types=1);

namespace App\Domains\Communication\DTOs;

final readonly class PresenceChannelData
{
    public function __construct(
        public int $id,
        public string $fullName,
    ) {}

    /** @return array{id: int, full_name: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
        ];
    }
}
