<?php

declare(strict_types=1);

namespace App\Domains\Admin\Http\Controllers;

use App\Domains\Admin\Http\Requests\AddGroupMemberRequest;
use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\GroupMembership;
use App\Domains\Communication\Models\Message;
use App\Domains\Identity\Http\Resources\UserResource;
use App\Domains\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupController
{
    public function members(Group $group): AnonymousResourceCollection
    {
        $userIds = GroupMembership::where('group_id', $group->id)
            ->whereNull('left_at')
            ->pluck('user_id');

        $users = User::whereIn('id', $userIds)->orderBy('id')->get();

        return UserResource::collection($users);
    }

    public function addMember(Group $group, AddGroupMemberRequest $request): JsonResponse
    {
        $userId = (int) $request->input('user_id');

        $exists = GroupMembership::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User is already a member.'], 409);
        }

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'added_by' => $request->user()->id,
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Member added.']);
    }

    public function removeMember(Group $group, User $user): JsonResponse
    {
        $membership = GroupMembership::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'User is not an active member.'], 404);
        }

        $membership->update(['left_at' => now()]);

        return response()->json(['message' => 'Member removed.']);
    }

    public function downloadMessages(Group $group, Request $request): StreamedResponse
    {
        $messages = Message::with('sender')
            ->where('group_id', $group->id)
            ->orderBy('sent_at', 'asc')
            ->get();

        $filename = sprintf('group-%d-messages-%s.csv', $group->id, now()->format('Y-m-d_Hi'));

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($messages) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Timestamp', 'Message', 'Sender Name']);

            foreach ($messages as $msg) {
                fputcsv($handle, [
                    $msg->sent_at ? $msg->sent_at->toIso8601String() : '',
                    $msg->message_text ?? '',
                    $msg->sender?->full_name ?? 'Unknown',
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
