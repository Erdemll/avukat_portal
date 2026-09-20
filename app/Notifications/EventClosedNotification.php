<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Notifications\Messages\MailMessage;

class EventClosedNotification extends LegalActivityNotification
{
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private Event $event)
    {
        $this->afterCommit();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Olay kapatıldı')->line($this->event->event_no.' · '.$this->event->title)->line('Olay kapatıldı.')->action('Olayı Görüntüle', route('events.show', $this->event));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'event_closed', 'event_id' => $this->event->id, 'event_no' => $this->event->event_no, 'title' => $this->event->title, 'message' => 'Olay kapatıldı.', 'url' => route('events.show', $this->event)];
    }
}
