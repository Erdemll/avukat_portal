<?php

namespace App\Notifications;

use App\Models\EventUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private EventUpdate $update)
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
