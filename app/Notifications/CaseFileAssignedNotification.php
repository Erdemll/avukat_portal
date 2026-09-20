<?php

namespace App\Notifications;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;

class CaseFileAssignedNotification extends LegalActivityNotification
{
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private CaseFile $caseFile)
    {
        $this->afterCommit();
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof User
            && $notifiable->is_active
            && $this->caseFile->assignments()
                ->where('lawyer_id', $notifiable->getKey())
                ->whereNull('ended_at')
                ->exists()
            && Gate::forUser($notifiable)->allows('view', $this->caseFile);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Yeni hukuki dosya atandı')
            ->line($this->caseFile->case_no.' · '.$this->caseFile->title)
            ->line('Yeni bir hukuki dosyada görevlendirildiniz.')
            ->action('Dosyayı Görüntüle', route('case-files.show', $this->caseFile));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case_file_assigned',
            'case_file_id' => $this->caseFile->id,
            'case_no' => $this->caseFile->case_no,
            'title' => $this->caseFile->title,
            'message' => 'Yeni hukuki dosya atandı.',
            'url' => route('case-files.show', $this->caseFile),
        ];
    }
}
