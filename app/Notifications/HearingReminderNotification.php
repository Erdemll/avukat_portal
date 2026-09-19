<?php

namespace App\Notifications;

use App\Models\Hearing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class HearingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private Hearing $hearing)
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

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof User
            && $notifiable->is_active
            && Gate::forUser($notifiable)->allows('view', $this->hearing->caseFile);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Yaklaşan duruşma: '.$this->hearing->title)
            ->line($this->hearing->caseFile->case_no.' · '.$this->hearing->caseFile->title)
            ->line('Duruşma: '.$this->hearing->hearing_at->format('d.m.Y H:i'))
            ->line($this->hearing->court ? 'Mahkeme: '.$this->hearing->court : 'Mahkeme bilgisi girilmemiştir.')
            ->action('Dosyayı Görüntüle', route('case-files.show', $this->hearing->caseFile));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hearing_reminder',
            'case_file_id' => $this->hearing->case_file_id,
            'hearing_id' => $this->hearing->id,
            'title' => $this->hearing->title,
            'hearing_at' => $this->hearing->hearing_at->toIso8601String(),
            'message' => 'Yaklaşan duruşma: '.$this->hearing->title,
        ];
    }
}
