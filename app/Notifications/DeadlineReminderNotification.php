<?php

namespace App\Notifications;

use App\Models\Deadline;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class DeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private Deadline $deadline)
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
            && Gate::forUser($notifiable)->allows('view', $this->deadline->caseFile);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Yaklaşan hukuki süre: '.$this->deadline->title)
            ->line($this->deadline->caseFile->case_no.' · '.$this->deadline->caseFile->title)
            ->line('Son tarih: '.$this->deadline->due_at->format('d.m.Y H:i'))
            ->action('Dosyayı Görüntüle', route('case-files.show', $this->deadline->caseFile));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deadline_reminder',
            'case_file_id' => $this->deadline->case_file_id,
            'deadline_id' => $this->deadline->id,
            'title' => $this->deadline->title,
            'due_at' => $this->deadline->due_at->toIso8601String(),
            'message' => 'Yaklaşan hukuki süre: '.$this->deadline->title,
        ];
    }
}
