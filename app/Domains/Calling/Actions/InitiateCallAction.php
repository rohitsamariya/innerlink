<?php

declare(strict_types=1);

namespace App\Domains\Calling\Actions;

use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\DTOs\CallData;
use App\Domains\Calling\Enums\CallStatus;
use App\Domains\Calling\Events\CallOfferSent;
use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class InitiateCallAction
{
    public function __construct(
        private CallRepositoryInterface $callRepository,
    ) {}

    public function execute(User $caller, int $receiverId): CallData
    {
        if ($caller->role !== Role::ADMIN) {
            $receiver = User::query()->findOrFail($receiverId);
            if ($receiver->role !== Role::ADMIN) {
                throw new AuthorizationException('Users can only call the admin.');
            }
        }

        $activeCall = $this->callRepository->getActiveCallForUser($caller->id);
        if ($activeCall) {
            throw new AuthorizationException('You already have an active call.');
        }

        $activeCall = $this->callRepository->getActiveCallForUser($receiverId);
        if ($activeCall) {
            throw new AuthorizationException('The user is currently in a call.');
        }

        $receiver = User::query()->findOrFail($receiverId);
        if (!$receiver->is_enabled) {
            throw new AuthorizationException('User is not available.');
        }

        return DB::transaction(function () use ($caller, $receiver) {
            $call = $this->callRepository->create([
                'caller_id' => $caller->id,
                'receiver_id' => $receiver->id,
                'status' => CallStatus::RINGING->value,
            ]);

            $data = new CallData(
                callId: $call->id,
                callerId: $caller->id,
                callerName: $caller->full_name,
                receiverId: $receiver->id,
                receiverName: $receiver->full_name,
                status: CallStatus::RINGING->value,
            );

            DB::afterCommit(function () use ($data) {
                try {
                    event(new CallOfferSent($data));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed for call offer {id}: {error}', [
                        'id' => $data->callId,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return $data;
        });
    }
}
