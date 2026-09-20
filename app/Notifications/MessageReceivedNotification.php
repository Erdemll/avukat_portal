<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class MessageReceivedNotification extends Notification
{
    public function __construct(
        private int $conversationId,
        private int $senderId,
        private string $senderName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'message_received',
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'message' => 'Av. '.$this->senderName.' size yeni bir mesaj gönderdi.',
            'url' => route('messages.index', ['conversation' => $this->conversationId]),
        ];
    }
}
