<?php

declare(strict_types=1);

namespace App\Domains\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Identity\Http\Requests\LoginRequest;
use App\Domains\Identity\Http\Resources\UserResource;
use App\Domains\Identity\Actions\AuthenticateUserAction;
use App\Domains\Identity\Actions\RevokeUserSessionAction;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    /**
     * Authenticate the user and return user resource with authentication token.
     *
     * @param LoginRequest $request
     * @param AuthenticateUserAction $action
     * @param UserRepositoryInterface $userRepository
     * @return UserResource
     */
    public function login(
        LoginRequest $request,
        AuthenticateUserAction $action,
        UserRepositoryInterface $userRepository
    ): UserResource {
        $sessionData = $action->execute(
            email: $request->input('email'),
            password: $request->input('password'),
            ipAddress: $request->ip() ?? '127.0.0.1',
            userAgent: $request->userAgent() ?? 'Unknown'
        );

        $user = $userRepository->findById($sessionData->userId);

        if (!$user) {
            abort(500, 'User not found after authentication.');
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return (new UserResource($user))->additional([
            'meta' => [
                'token' => $token,
            ],
        ]);
    }

    /**
     * Revoke the user session and log out the user.
     *
     * @param Request $request
     * @param RevokeUserSessionAction $action
     * @param UserRepositoryInterface $userRepository
     * @return Response
     */
    public function logout(
        Request $request,
        RevokeUserSessionAction $action,
        UserRepositoryInterface $userRepository
    ): Response {
        $user = $request->user();

        if ($user) {
            $latestHistory = $userRepository->findLatestActiveLoginHistory($user->id);
            if ($latestHistory) {
                $action->execute($latestHistory->id, 'USER_INITIATED');
            }

            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }

        return response()->noContent();
    }

    /**
     * Return the authenticated user.
     *
     * @param Request $request
     * @return UserResource
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
