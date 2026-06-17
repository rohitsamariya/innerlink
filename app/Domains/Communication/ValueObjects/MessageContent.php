<?php

declare(strict_types=1);

namespace App\Domains\Communication\ValueObjects;

use InvalidArgumentException;

final readonly class MessageContent
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Message content cannot be empty.');
        }
        if (mb_strlen($trimmed) > 5000) {
            throw new InvalidArgumentException('Message content exceeds maximum length of 5000 characters.');
        }
        $this->value = $trimmed;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(MessageContent $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
