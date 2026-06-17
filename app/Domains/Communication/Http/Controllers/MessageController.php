<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Http\Requests\SendMessageRequest;
use App\Domains\Communication\Http\Resources\MessageResource;
use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Communication\Actions\DispatchMessageAction;
use App\Domains\Communication\Actions\MarkMessageDeliveredAction;
use App\Domains\Communication\DTOs\MessageData;
use App\Domains\Communication\Http\Requests\SearchMessagesRequest;
use App\Domains\Communication\ValueObjects\MessageContent;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    /**
     * Retrieve messages for a group, authorizing via GroupPolicy.
     *
     * @param Group $group
     * @param Request $request
     * @param MessageRepositoryInterface $messageRepository
     * @return AnonymousResourceCollection
     */
    public function index(
        Group $group,
        Request $request,
        MessageRepositoryInterface $messageRepository
    ): AnonymousResourceCollection {
        Gate::authorize('view', $group);

        $viewerId = $request->user()->id;
        $messages = $messageRepository->getGroupMessages($group->id, $viewerId);

        return MessageResource::collection($messages);
    }

    /**
     * Send a new message to a group, authorizing via GroupPolicy.
     *
     * @param Group $group
     * @param SendMessageRequest $request
     * @param DispatchMessageAction $action
     * @return MessageResource
     */
    public function store(
        Group $group,
        SendMessageRequest $request,
        DispatchMessageAction $action
    ): MessageResource {
        Gate::authorize('view', $group);

        if ($group->is_enabled === false) {
            abort(403, 'This group is disabled.');
        }

        $messageData = new MessageData(
            groupId: $group->id,
            senderId: $request->user()->id,
            content: new MessageContent($request->input('message_text'))
        );

        $message = $action->execute($messageData);

        return new MessageResource($message);
    }

    /**
     * Search messages within a group, authorizing via GroupPolicy.
     *
     * @param Group $group
     * @param SearchMessagesRequest $request
     * @param MessageRepositoryInterface $messageRepository
     * @return AnonymousResourceCollection
     */
    public function deliver(
        Group $group,
        Message $message,
        Request $request,
        MarkMessageDeliveredAction $action
    ): JsonResponse {
        Gate::authorize('view', $group);

        $action->execute(
            messageId: $message->id,
            groupId: $group->id,
            userId: $request->user()->id,
        );

        return response()->json(['status' => 'delivered']);
    }

    public function markRead(
        Group $group,
        Request $request,
        MessageRepositoryInterface $messageRepository
    ): JsonResponse {
        Gate::authorize('view', $group);

        $messageIds = $request->validate(['message_ids' => 'required|array', 'message_ids.*' => 'integer']);
        $userId = $request->user()->id;

        foreach ($messageIds['message_ids'] as $messageId) {
            $messageRepository->markAsRead((int) $messageId, $userId);
        }

        return response()->json(['status' => 'ok']);
    }

    public function readers(
        Group $group,
        Message $message,
        MessageRepositoryInterface $messageRepository
    ): JsonResponse {
        Gate::authorize('view', $group);

        $readers = $messageRepository->getReaders((int) $message->id);

        $data = [];
        foreach ($readers as $reader) {
            $data[] = [
                'user_id' => $reader->user_id,
                'full_name' => $reader->user?->full_name ?? 'Unknown',
                'read_at' => $reader->read_at instanceof DateTimeInterface
                    ? $reader->read_at->format(DateTimeInterface::ATOM)
                    : (is_string($reader->read_at) ? $reader->read_at : null),
            ];
        }

        return response()->json(['readers' => $data]);
    }

    public function search(
        Group $group,
        SearchMessagesRequest $request,
        MessageRepositoryInterface $messageRepository
    ): AnonymousResourceCollection {
        Gate::authorize('view', $group);

        $messages = $messageRepository->searchMessages(
            groupId: $group->id,
            viewerId: $request->user()->id,
            query: $request->input('q')
        );

        return MessageResource::collection($messages);
    }
}
