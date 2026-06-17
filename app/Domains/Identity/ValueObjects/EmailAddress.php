<?php

declare(strict_types=1);

namespace App\Domains\Identity\ValueObjects;

use InvalidArgumentException;

final readonly class EmailAddress
{
    private string $value;

    public function __construct(string $value)
    {
        $cleaned = trim($value);
        if (filter_var($cleaned, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('Invalid email address: "%s"', $value));
        }
        $this->value = $cleaned;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(EmailAddress $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
