<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Controllers;

use App\Domains\Communication\Actions\MarkUserTypingAction;
use App\Domains\Communication\Exceptions\NotGroupMemberException;
use App\Domains\Communication\Http\Requests\TypingRequest;
use App\Domains\Communication\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class TypingController extends Controller
{
    public function typing(
        Group $group,
        TypingRequest $request,
        MarkUserTypingAction $action,
    ): JsonResponse {
        $user = $request->user();

        try {
            $action->execute(
                groupId: $group->id,
                userId: $user->id,
                userName: $user->full_name,
                action: $request->input('action'),
            );
        } catch (NotGroupMemberException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['status' => 'ok']);
    }
}
