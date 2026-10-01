<?php

namespace App\Notifications;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;

class CaseFileAssistantAssignedNotification extends LegalActivityNotification
{
    public function __construct(private CaseFile $caseFile)
    {
        $this->afterCommit();
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return parent::shouldSend($notifiable, $channel)
            && $notifiable instanceof User
            && $notifiable->isAssistant()
            && $this->caseFile->assistants()->whereKey($notifiable->getKey())->exists()
            && Gate::forUser($notifiable)->allows('view', $this->caseFile);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hukuki dosyaya eklendiniz')
            ->line($this->caseFile->case_no.' · '.$this->caseFile->title)
            ->line('Bu hukuki dosyanın içeriğine erişebilir ve dosyada çalışabilirsiniz.')
            ->action('Dosyayı Görüntüle', route('case-files.show', $this->caseFile));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case_file_assistant_assigned',
            'case_file_id' => $this->caseFile->id,
            'case_no' => $this->caseFile->case_no,
            'title' => $this->caseFile->title,
            'message' => 'Hukuki dosyaya eklendiniz.',
            'url' => route('case-files.show', $this->caseFile),
        ];
    }
}
