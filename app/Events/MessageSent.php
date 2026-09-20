<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<int, int>  $participantIds
     */
    public function __construct(public Message $message, public array $participantIds) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
            ...array_map(
                fn (int $participantId): PrivateChannel => new PrivateChannel('user.'.$participantId),
                $this->participantIds,
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array{message: array{id: int, conversation_id: int, sender_id: int, sender_name: string, body: string, created_at: string}} */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender->name,
                'body' => $this->message->body,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
        ];
    }
}
