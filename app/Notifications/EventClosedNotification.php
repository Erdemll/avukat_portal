<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private Event $event)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
