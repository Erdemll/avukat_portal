<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentUploadedNotification extends LegalActivityNotification
{
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private Document $document)
    {
        $this->afterCommit();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Yeni belge yüklendi')->line($this->document->event->event_no.' · '.$this->document->event->title)->line('Yeni belge yüklendi: '.$this->document->original_name)->action('Olayı Görüntüle', route('events.show', $this->document->event));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'document_uploaded', 'event_id' => $this->document->event_id, 'event_no' => $this->document->event->event_no, 'title' => $this->document->event->title, 'message' => 'Yeni belge yüklendi: '.$this->document->original_name, 'url' => route('events.show', $this->document->event)];
    }
}
