<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Controllers;

use App\Domains\Communication\Events\PrivateMessageRead;
use App\Domains\Communication\Events\PrivateMessageSent;
use App\Domains\Communication\Http\Resources\PrivateMessageResource;
use App\Domains\Communication\Models\PrivateMessage;
use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\Http\Resources\UserResource;
use App\Domains\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrivateMessageController
{
    public function index(Request $request, User $otherUser): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $messages = PrivateMessage::with('sender', 'receiver')
            ->where(function ($q) use ($userId, $otherUser) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherUser->id);
            })->orWhere(function ($q) use ($userId, $otherUser) {
                $q->where('sender_id', $otherUser->id)->where('receiver_id', $userId);
            })
            ->orderBy('sent_at', 'desc')
            ->take(500)
            ->get()
            ->reverse()
            ->values();

        return PrivateMessageResource::collection($messages);
    }

    public function store(Request $request, User $otherUser): JsonResponse
    {
        $sender = $request->user();
        $receiver = $otherUser;

        $allowed = $sender->role === Role::ADMIN
            || ($sender->role === Role::MANAGER && $receiver->role === Role::ADMIN)
            || ($sender->role === Role::EMPLOYEE && $receiver->role === Role::ADMIN);

        if (!$allowed) {
            return response()->json(['message' => 'You are not allowed to message this user.'], 403);
        }

        $validated = $request->validate([
            'message_text' => 'required|string|max:10000',
        ]);

        $message = PrivateMessage::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message_text' => $validated['message_text'],
        ]);

        $message->load('sender', 'receiver');

        try {
            broadcast(new PrivateMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Private message broadcast failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'data' => new PrivateMessageResource($message),
        ]);
    }

    public function markRead(Request $request, User $otherUser): JsonResponse
    {
        $userId = $request->user()->id;
        $now = now();

        $updated = DB::table('private_messages')
            ->where('sender_id', $otherUser->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        if ($updated > 0) {
            broadcast(new PrivateMessageRead(
                readerId: $userId,
                senderId: $otherUser->id,
                readAt: $now->toIso8601String()
            ));
        }

        return response()->json(['status' => 'ok']);
    }

    public function contacts(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $users = match ($user->role) {
            Role::ADMIN => User::whereIn('role', [Role::ADMIN, Role::MANAGER, Role::EMPLOYEE]),
            default => User::where('role', Role::ADMIN),
        };

        $users = $users
            ->where('id', '!=', $user->id)
            ->orderBy('full_name')
            ->paginate(50);

        return UserResource::collection($users);
    }

    public function profile(Request $request, User $user): UserResource
    {
        $currentUser = $request->user();

        $allowed = $currentUser->id === $user->id
            || $currentUser->role === Role::ADMIN
            || $user->role === Role::ADMIN;

        abort_unless($allowed, 403, 'You are not allowed to view this profile.');

        return new UserResource($user);
    }

    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $partnerIds = PrivateMessage::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->selectRaw('DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as id', [$userId])
            ->pluck('id');

        if ($partnerIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $users = User::whereIn('id', $partnerIds)->get()->keyBy('id');

        $latestMessageIds = PrivateMessage::where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->selectRaw('MAX(id) as id')
            ->groupBy(DB::raw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)'))
            ->pluck('id');

        $latestMessages = PrivateMessage::whereIn('id', $latestMessageIds)
            ->get()
            ->keyBy(fn ($m) => $m->sender_id === $userId ? $m->receiver_id : $m->sender_id);

        $unreadCounts = PrivateMessage::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as count')
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id');

        $result = [];
        foreach ($partnerIds as $partnerId) {
            $lastMessage = $latestMessages->get($partnerId);

            $result[] = [
                'user' => $users->get($partnerId) ? new UserResource($users->get($partnerId)) : null,
                'last_message' => $lastMessage?->message_text,
                'last_message_at' => $lastMessage?->sent_at?->toIso8601String(),
                'unread_count' => (int) ($unreadCounts->get($partnerId) ?? 0),
            ];
        }

        usort($result, fn ($a, $b) => ($b['last_message_at'] ?? '') <=> ($a['last_message_at'] ?? ''));

        return response()->json(['data' => $result]);
    }
}
