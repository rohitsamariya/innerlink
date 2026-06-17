<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\DTOs\SessionData;
use App\Domains\Identity\Exceptions\AuthenticationFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use DateTimeImmutable;

final readonly class AuthenticateUserAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Authenticate user credentials, create a login history record, and return session data.
     *
     * @param string $email
     * @param string $password
     * @param string $ipAddress
     * @param string $userAgent
     * @return SessionData
     * @throws AuthenticationFailedException
     */
    public function execute(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent
    ): SessionData {
        return DB::transaction(function () use ($email, $password, $ipAddress, $userAgent) {
            $user = $this->userRepository->findByEmail($email);

            if (!$user || !Hash::check($password, $user->password)) {
                throw AuthenticationFailedException::invalidCredentials();
            }

            if (!$user->is_enabled) {
                throw AuthenticationFailedException::userDisabled();
            }

            $loginHistory = $this->userRepository->recordLogin($user->id, $ipAddress, $userAgent);

            // Convert logged_in_at Carbon/DateTime to ISO-8601 string for DateTimeImmutable
            $loggedInAt = $loginHistory->logged_in_at;
            $loggedInAtStr = ($loggedInAt instanceof \DateTimeInterface)
                ? $loggedInAt->format(\DateTimeInterface::ATOM)
                : (is_string($loggedInAt) ? $loggedInAt : 'now');

            return new SessionData(
                userId: $user->id,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                loggedInAt: new DateTimeImmutable($loggedInAtStr)
            );
        });
    }
}
