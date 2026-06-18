<?php

declare(strict_types=1);

namespace App\Domains\Calling\Actions;

use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\DTOs\CallData;
use App\Domains\Calling\Enums\CallStatus;
use App\Domains\Calling\Events\CallRejected;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class RejectCallAction
{
    public function __construct(
        private CallRepositoryInterface $callRepository,
    ) {}

    public function execute(int $callId, User $user): CallData
    {
        $call = $this->callRepository->findById($callId);

        if (!$call || $call->receiver_id !== $user->id) {
            throw new AuthorizationException('You are not the receiver of this call.');
        }

        if ($call->status !== CallStatus::RINGING) {
            throw new AuthorizationException('Call is no longer ringing.');
        }

        return DB::transaction(function () use ($call) {
            $this->callRepository->updateStatus($call->id, CallStatus::REJECTED->value);

            $data = new CallData(
                callId: $call->id,
                callerId: $call->caller_id,
                callerName: $call->caller?->full_name ?? 'Unknown',
                receiverId: $call->receiver_id,
                receiverName: $call->receiver?->full_name ?? 'Unknown',
                status: CallStatus::REJECTED->value,
            );

            DB::afterCommit(function () use ($data) {
                try {
                    event(new CallRejected($data));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed for call rejected {id}: {error}', [
                        'id' => $data->callId,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return $data;
        });
    }
}
