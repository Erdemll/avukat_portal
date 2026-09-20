<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Notifications\Messages\MailMessage;

class EventAssignedNotification extends LegalActivityNotification
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
        return (new MailMessage)->subject('Yeni olay atandı')->line($this->event->event_no.' · '.$this->event->title)->line('Yeni bir olay size atandı.')->action('Olayı Görüntüle', route('events.show', $this->event));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'event_assigned', 'event_id' => $this->event->id, 'event_no' => $this->event->event_no, 'title' => $this->event->title, 'message' => 'Yeni olay atandı.', 'url' => route('events.show', $this->event)];
    }
}
