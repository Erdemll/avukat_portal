<?php

namespace App\Services\Messaging;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReceivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageService
{
    public function send(Conversation $conversation, User $sender, string $body): Message
    {
        $message = DB::transaction(fn (): Message => $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]));

        $message->load('sender:id,name');
        $participants = $conversation->participants()
            ->select(['users.id', 'users.is_active'])
            ->get();
        $participantIds = $participants->modelKeys();

        foreach ($participants->where('id', '!=', $sender->id)->where('is_active', true) as $recipient) {
            try {
                $recipient->notify(new MessageReceivedNotification(
                    $conversation->id,
                    $sender->id,
                    $sender->name,
                ));
            } catch (Throwable $exception) {
                Log::warning('Message notification could not be stored.', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'user_id' => $recipient->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        try {
            $event = new MessageSent($message, $participantIds);
            $event->dontBroadcastToCurrentUser();
            event($event);
        } catch (Throwable $exception) {
            Log::warning('Realtime message broadcast failed.', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'exception' => $exception::class,
            ]);
        }

        return $message;
    }

    public function markRead(Conversation $conversation, User $user): int
    {
        $conversation->participantRecords()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return $user->unreadNotifications()
            ->where('type', MessageReceivedNotification::class)
            ->where('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);
    }
}
