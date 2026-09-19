<?php

namespace App\Notifications;

use App\CaseAssignmentRequestStatus;
use App\Models\CaseAssignmentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseAssignmentRequestDecidedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private CaseAssignmentRequest $request)
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
        return (new MailMessage)->subject('Dosya talebi sonuçlandırıldı')
            ->line($this->request->caseFile->case_no.' · '.$this->request->status->label())
            ->action('Talebi Görüntüle', route('assignment-requests.index'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'case_assignment_decided', 'case_file_id' => $this->request->case_file_id, 'message' => $this->request->type->label().' '.$this->request->status->label().'.', 'url' => route('assignment-requests.index')];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof User && $notifiable->is_active && $this->request->fresh()->status !== CaseAssignmentRequestStatus::Pending;
    }
}
