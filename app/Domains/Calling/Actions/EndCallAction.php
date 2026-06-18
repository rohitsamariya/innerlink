<?php

declare(strict_types=1);

namespace App\Domains\Calling\Actions;

use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\DTOs\CallData;
use App\Domains\Calling\Enums\CallStatus;
use App\Domains\Calling\Events\CallEnded;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class EndCallAction
{
    public function __construct(
        private CallRepositoryInterface $callRepository,
    ) {}

    public function execute(int $callId, User $user): CallData
    {
        $call = $this->callRepository->findById($callId);

        if (!$call || !$this->callRepository->isParticipant($callId, $user->id)) {
            throw new AuthorizationException('You are not a participant in this call.');
        }

        if (!in_array($call->status->value, ['ringing', 'accepted'])) {
            throw new AuthorizationException('Call is not active.');
        }

        return DB::transaction(function () use ($call) {
            $now = now();
            $duration = null;

            if ($call->started_at) {
                $duration = (int) $now->diffInSeconds($call->started_at);
            }

            $status = $call->started_at ? CallStatus::ENDED->value : CallStatus::MISSED->value;

            $this->callRepository->updateStatus($call->id, $status, [
                'ended_at' => $now,
                'duration_seconds' => $duration,
            ]);

            $data = new CallData(
                callId: $call->id,
                callerId: $call->caller_id,
                callerName: $call->caller?->full_name ?? 'Unknown',
                receiverId: $call->receiver_id,
                receiverName: $call->receiver?->full_name ?? 'Unknown',
                status: $status,
                startedAt: $call->started_at?->toIso8601String(),
                endedAt: $now->toIso8601String(),
                durationSeconds: $duration,
            );

            DB::afterCommit(function () use ($data) {
                try {
                    event(new CallEnded($data));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed for call ended {id}: {error}', [
                        'id' => $data->callId,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return $data;
        });
    }
}
