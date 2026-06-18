<?php

declare(strict_types=1);

namespace App\Domains\Calling\Actions;

use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\Events\IceCandidateSent;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

final readonly class SendIceCandidateAction
{
    public function __construct(
        private CallRepositoryInterface $callRepository,
    ) {}

    public function execute(int $callId, User $user, array $candidate): void
    {
        $call = $this->callRepository->findById($callId);

        if (!$call || !$this->callRepository->isParticipant($callId, $user->id)) {
            throw new AuthorizationException('You are not a participant in this call.');
        }

        try {
            event(new IceCandidateSent(
                callId: $callId,
                candidate: $candidate,
                userId: $user->id,
            ));
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed for ICE candidate on call {id}: {error}', [
                'id' => $callId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
