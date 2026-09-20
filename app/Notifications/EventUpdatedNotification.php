<?php

namespace App\Notifications;

use App\Models\EventUpdate;
use Illuminate\Notifications\Messages\MailMessage;

class EventUpdatedNotification extends LegalActivityNotification
{
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private EventUpdate $update)
    {
        $this->afterCommit();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Yeni süreç güncellemesi')->line($this->update->event->event_no.' · '.$this->update->event->title)->line('Portala giriş yaparak görüntüleyebilirsiniz.')->action('Olayı Görüntüle', route('events.show', $this->update->event));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'event_updated', 'event_id' => $this->update->event_id, 'event_no' => $this->update->event->event_no, 'title' => $this->update->event->title, 'message' => 'Yeni süreç güncellemesi var.', 'url' => route('events.show', $this->update->event)];
    }
}
