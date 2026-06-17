<?php

declare(strict_types=1);

namespace App\Domains\Identity\DTOs;

use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\ValueObjects\EmailAddress;

final readonly class UserRegistrationData
{
    public function __construct(
        public string $fullName,
        public EmailAddress $email,
        public string $clearPassword,
        public Role $role = Role::EMPLOYEE
    ) {}
}
