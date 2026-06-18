<?php

declare(strict_types=1);

namespace App\Domains\Calling\Http\Controllers;

use App\Domains\Calling\Actions\AcceptCallAction;
use App\Domains\Calling\Actions\EndCallAction;
use App\Domains\Calling\Actions\InitiateCallAction;
use App\Domains\Calling\Actions\RejectCallAction;
use App\Domains\Calling\Actions\SendIceCandidateAction;
use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\Http\Requests\IceCandidateRequest;
use App\Domains\Calling\Http\Requests\InitiateCallRequest;
use App\Domains\Calling\Policies\CallPolicy;
use App\Domains\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CallController extends Controller
{
    public function __construct(
        private InitiateCallAction $initiateCall,
        private AcceptCallAction $acceptCall,
        private RejectCallAction $rejectCall,
        private EndCallAction $endCall,
        private SendIceCandidateAction $sendIceCandidate,
        private CallRepositoryInterface $callRepository,
        private CallPolicy $callPolicy,
    ) {}

    public function initiate(InitiateCallRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$this->callPolicy->initiate($user, $request->integer('receiver_id'))) {
            return response()->json(['message' => 'Users can only call the admin.'], 403);
        }

        $callData = $this->initiateCall->execute($user, $request->integer('receiver_id'));

        return response()->json($callData->toArray(), 201);
    }

    public function accept(int $callId, \Illuminate\Http\Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $callData = $this->acceptCall->execute($callId, $user);

        return response()->json($callData->toArray());
    }

    public function reject(int $callId, \Illuminate\Http\Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $callData = $this->rejectCall->execute($callId, $user);

        return response()->json($callData->toArray());
    }

    public function end(int $callId, \Illuminate\Http\Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $callData = $this->endCall->execute($callId, $user);

        return response()->json($callData->toArray());
    }

    public function iceCandidate(int $callId, IceCandidateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->sendIceCandidate->execute($callId, $user, $request->input('candidate'));

        return response()->json(['message' => 'ICE candidate sent.'], 200);
    }

    public function history(\Illuminate\Http\Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $history = $this->callRepository->getHistoryForUser($user->id);

        return response()->json($history);
    }

    public function active(\Illuminate\Http\Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $call = $this->callRepository->getActiveCallForUser($user->id);

        if (!$call) {
            return response()->json(null, 204);
        }

        return response()->json([
            'id' => $call->id,
            'caller_id' => $call->caller_id,
            'caller_name' => $call->caller?->full_name,
            'receiver_id' => $call->receiver_id,
            'receiver_name' => $call->receiver?->full_name,
            'status' => $call->status->value,
            'started_at' => $call->started_at?->toIso8601String(),
        ]);
    }
}
