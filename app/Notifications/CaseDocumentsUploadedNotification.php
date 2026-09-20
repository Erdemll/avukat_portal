<?php

namespace App\Notifications;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;

class CaseDocumentsUploadedNotification extends LegalActivityNotification
{
    /**
     * @param  array<int, string>  $documentNames
     */
    public function __construct(
        private CaseFile $caseFile,
        private array $documentNames,
    ) {
        $this->afterCommit();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->documentNames);

        $mail = (new MailMessage)
            ->subject($count === 1 ? 'Hukuki dosyaya yeni belge eklendi' : "Hukuki dosyaya {$count} yeni belge eklendi")
            ->line($this->caseFile->case_no.' · '.$this->caseFile->title);

        foreach (array_slice($this->documentNames, 0, 5) as $documentName) {
            $mail->line('Belge: '.$documentName);
        }

        if ($count > 5) {
            $mail->line('Diğer belgelerle birlikte toplam '.$count.' belge yüklendi.');
        }

        return $mail->action('Dosyayı Görüntüle', route('case-files.show', $this->caseFile));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $count = count($this->documentNames);

        return [
            'type' => 'case_documents_uploaded',
            'case_file_id' => $this->caseFile->id,
            'message' => $count === 1
                ? 'Hukuki dosyaya yeni belge eklendi.'
                : "Hukuki dosyaya {$count} yeni belge eklendi.",
            'url' => route('case-files.show', $this->caseFile),
        ];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return parent::shouldSend($notifiable, $channel)
            && $notifiable instanceof User
            && Gate::forUser($notifiable)->allows('view', $this->caseFile);
    }
}
