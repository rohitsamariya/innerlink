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
            ->orderBy('sent_at')
            ->get();

        return PrivateMessageResource::collection($messages);
    }

    public function store(Request $request, User $otherUser): JsonResponse
    {
        $validated = $request->validate([
            'message_text' => 'required|string|max:10000',
        ]);

        $message = PrivateMessage::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $otherUser->id,
            'message_text' => $validated['message_text'],
        ]);

        $message->load('sender', 'receiver');

        broadcast(new PrivateMessageSent($message))->toOthers();

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

        $targetRole = $user->role === Role::ADMIN ? Role::MANAGER : Role::ADMIN;

        $users = User::where('role', $targetRole)
            ->where('id', '!=', $user->id)
            ->orderBy('full_name')
            ->get();

        return UserResource::collection($users);
    }

    public function profile(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $otherUserIds = PrivateMessage::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->get()
            ->map(fn ($m) => $m->sender_id === $userId ? $m->receiver_id : $m->sender_id)
            ->unique()
            ->values();

        $result = [];
        foreach ($otherUserIds as $otherId) {
            $lastMessage = PrivateMessage::where(function ($q) use ($userId, $otherId) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherId);
            })->orWhere(function ($q) use ($userId, $otherId) {
                $q->where('sender_id', $otherId)->where('receiver_id', $userId);
            })->orderBy('sent_at', 'desc')->first();

            $unreadCount = PrivateMessage::where('sender_id', $otherId)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $otherUser = User::find($otherId);

            $result[] = [
                'user' => $otherUser ? new UserResource($otherUser) : null,
                'last_message' => $lastMessage?->message_text,
                'last_message_at' => $lastMessage?->sent_at?->toIso8601String(),
                'unread_count' => $unreadCount,
            ];
        }

        usort($result, fn ($a, $b) => ($b['last_message_at'] ?? '') <=> ($a['last_message_at'] ?? ''));

        return response()->json(['data' => $result]);
    }
}
