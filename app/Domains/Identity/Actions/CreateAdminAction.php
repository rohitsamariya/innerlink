<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Admin\Exceptions\AdminViolationException;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\DTOs\UserRegistrationData;
use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\ValueObjects\EmailAddress;
use Illuminate\Database\QueryException;

final readonly class CreateAdminAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Create the single application admin user.
     *
     * Uniqueness is enforced at the database level via the `uq_single_admin`
     * partial unique index. If the index is violated (concurrent creation or
     * an admin already exists) a domain-level AdminViolationException is thrown.
     *
     * @param string $name      Full name of the admin.
     * @param string $email     Valid email address.
     * @param string $password  Clear-text password (hashed inside the repository).
     * @return object           The newly created User model returned by the repository.
     * @throws AdminViolationException When an ADMIN already exists.
     * @throws \InvalidArgumentException When email format is invalid.
     */
    public function execute(string $name, string $email, string $password): object
    {
        $dto = new UserRegistrationData(
            fullName: $name,
            email: new EmailAddress($email),
            clearPassword: $password,
            role: Role::ADMIN,
        );

        try {
            return $this->userRepository->create($dto);
        } catch (QueryException $e) {
            // Detect violation of the uq_single_admin partial unique index.
            // PostgreSQL error code 23505 = unique_violation.
            if ($e->getCode() === '23505') {
                throw new AdminViolationException(
                    'An ADMIN user already exists. Only one ADMIN is permitted.'
                );
            }

            throw $e;
        }
    }
}
