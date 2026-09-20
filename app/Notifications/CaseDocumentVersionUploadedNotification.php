<?php

namespace App\Notifications;

use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;

class CaseDocumentVersionUploadedNotification extends LegalActivityNotification
{
    public function __construct(private DocumentVersion $version)
    {
        $this->afterCommit();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->version->document;
        $caseFile = $document->caseFile;

        return (new MailMessage)
            ->subject('Hukuki dosya belgesine yeni sürüm eklendi')
            ->line($caseFile->case_no.' · '.$caseFile->title)
            ->line($document->title.' belgesinin '.$this->version->version_no.'. sürümü yüklendi.')
            ->action('Dosyayı Görüntüle', route('case-files.show', $caseFile));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $document = $this->version->document;

        return [
            'type' => 'case_document_version_uploaded',
            'case_file_id' => $document->case_file_id,
            'document_id' => $document->id,
            'message' => $document->title.' belgesinin '.$this->version->version_no.'. sürümü yüklendi.',
            'url' => route('case-files.show', $document->caseFile),
        ];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return parent::shouldSend($notifiable, $channel)
            && $notifiable instanceof User
            && Gate::forUser($notifiable)->allows('view', $this->version->document->caseFile);
    }
}
