<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\ConversationService;
use App\Services\Messaging\MessageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Conversation::class);

        $lawyers = User::query()
            ->select(['id', 'name', 'email'])
            ->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))
            ->where('is_active', true)
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->get();

        $conversations = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->whereKey($request->user()->id))
            ->with([
                'participants:id,name,is_active',
                'latestMessage' => fn ($query) => $query->select([
                    'messages.id',
                    'messages.conversation_id',
                    'messages.sender_id',
                    'messages.body',
                    'messages.created_at',
                ]),
            ])
            ->get();

        $unreadCounts = $this->unreadCounts($conversations, $request->user());
        $conversationsByLawyer = $conversations->keyBy(
            fn (Conversation $conversation): ?int => $conversation->participants->firstWhere('id', '!=', $request->user()->id)?->id,
        );

        $lawyerData = $lawyers->map(function (User $lawyer) use ($conversationsByLawyer, $unreadCounts): array {
            $conversation = $conversationsByLawyer->get($lawyer->id);
            $lastMessage = $conversation?->latestMessage;

            return [
                'id' => $lawyer->id,
                'name' => $lawyer->name,
                'email' => $lawyer->email,
                'conversation_id' => $conversation?->id,
                'last_message' => $lastMessage?->body,
                'last_message_at' => $lastMessage?->created_at?->toIso8601String(),
                'unread_count' => $conversation === null ? 0 : ($unreadCounts[$conversation->id] ?? 0),
            ];
        });

        return view('messages.index', [
            'lawyers' => $lawyerData,
            'currentUserId' => $request->user()->id,
            'initialConversationId' => $conversations->firstWhere('id', $request->integer('conversation'))?->id,
            'initialLawyerId' => $lawyers->firstWhere('id', $request->integer('lawyer'))?->id,
        ]);
    }

    public function storeConversation(StoreConversationRequest $request, ConversationService $service): JsonResponse
    {
        $target = User::query()->findOrFail($request->integer('user_id'));
        $conversation = $service->findOrCreateDirect($request->user(), $target);

        return response()->json($this->conversationEndpoints($conversation));
    }

    public function showConversation(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $query = $conversation->messages()
            ->with('sender:id,name')
            ->latest('id');

        if ($request->filled('before')) {
            $query->whereKey('<', $request->integer('before'));
        }

        $messages = $query->limit(51)->get();
        $hasMore = $messages->count() > 50;
        $messages = $messages->take(50)->reverse()->values();
        $otherParticipant = $conversation->participants()
            ->select(['users.id', 'users.name', 'users.is_active'])
            ->whereKeyNot($request->user()->id)
            ->firstOrFail();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'participant' => [
                    'id' => $otherParticipant->id,
                    'name' => $otherParticipant->name,
                    'is_active' => $otherParticipant->is_active,
                ],
                ...$this->conversationEndpoints($conversation),
            ],
            'messages' => $messages->map(fn (Message $message): array => $this->messageData($message)),
            'pagination' => [
                'has_more' => $hasMore,
                'next_before' => $hasMore ? $messages->first()?->id : null,
            ],
        ]);
    }

    public function storeMessage(SendMessageRequest $request, Conversation $conversation, MessageService $service): JsonResponse
    {
        $message = $service->send($conversation, $request->user(), $request->string('body')->toString());

        return response()->json(['message' => $this->messageData($message)], 201);
    }

    public function readConversation(Request $request, Conversation $conversation, MessageService $service): JsonResponse
    {
        Gate::authorize('markRead', $conversation);
        $notificationsRead = $service->markRead($conversation, $request->user());

        return response()->json([
            'read_at' => now()->toIso8601String(),
            'notifications_read' => $notificationsRead,
        ]);
    }

    /**
     * @param  Collection<int, Conversation>  $conversations
     * @return array<int, int>
     */
    private function unreadCounts(Collection $conversations, User $user): array
    {
        if ($conversations->isEmpty()) {
            return [];
        }

        return Message::query()
            ->selectRaw('messages.conversation_id, COUNT(*) as aggregate')
            ->join('conversation_participants as participant', function ($join) use ($user): void {
                $join->on('participant.conversation_id', '=', 'messages.conversation_id')
                    ->where('participant.user_id', '=', $user->id);
            })
            ->whereIn('messages.conversation_id', $conversations->modelKeys())
            ->where('messages.sender_id', '!=', $user->id)
            ->where(function ($query): void {
                $query->whereNull('participant.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'participant.last_read_at');
            })
            ->groupBy('messages.conversation_id')
            ->pluck('aggregate', 'messages.conversation_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /** @return array{id: int, conversation_id: int, sender_id: int, sender_name: string, body: string, created_at: string} */
    private function messageData(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /** @return array{id: int, show_url: string, message_store_url: string, read_url: string} */
    private function conversationEndpoints(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'show_url' => route('messages.conversations.show', $conversation),
            'message_store_url' => route('messages.store', $conversation),
            'read_url' => route('messages.read', $conversation),
        ];
    }
}
