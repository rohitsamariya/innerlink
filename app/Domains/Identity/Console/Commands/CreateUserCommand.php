<?php

declare(strict_types=1);

namespace App\Domains\Identity\Console\Commands;

use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\DTOs\UserRegistrationData;
use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\ValueObjects\EmailAddress;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateUserCommand extends Command
{
    protected $signature = 'innerlink:create-user
                            {name  : Full name of the user}
                            {email : Email address for the user}
                            {password : Password for the user}
                            {--role=EMPLOYEE : Role (ADMIN, MANAGER, or EMPLOYEE)}';

    protected $description = 'Create a user account.';

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $email = (string) $this->argument('email');
        $password = (string) $this->argument('password');
        $role = strtoupper((string) $this->option('role'));

        $roleEnum = Role::tryFrom($role);
        if (!$roleEnum) {
            $this->error("Invalid role: $role. Must be ADMIN, MANAGER, or EMPLOYEE.");
            return self::FAILURE;
        }

        try {
            $dto = new UserRegistrationData(
                fullName: $name,
                email: new EmailAddress($email),
                clearPassword: $password,
                role: $roleEnum,
            );

            $this->userRepository->create($dto);
        } catch (InvalidArgumentException $e) {
            $this->error('Invalid input: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("User \"{$name}\" <{$email}> created successfully with role {$roleEnum->value}.");
        return self::SUCCESS;
    }
}
