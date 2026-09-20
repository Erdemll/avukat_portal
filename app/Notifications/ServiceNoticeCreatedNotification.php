<?php

namespace App\Notifications;

use App\Models\ServiceNotice;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class ServiceNoticeCreatedNotification extends LegalActivityNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(private ServiceNotice $notice)
    {
        $this->afterCommit();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Yeni tebligat kaydı')
            ->line($this->notice->caseFile->case_no.' · Tebliğ tarihi '.$this->notice->service_date->format('d.m.Y'))
            ->action('Tebligatı Görüntüle', route('service-notices.edit', $this->notice));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'service_notice_created', 'case_file_id' => $this->notice->case_file_id, 'message' => 'Yeni tebligat kaydı oluşturuldu.', 'url' => route('service-notices.edit', $this->notice)];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof User && $notifiable->is_active && $notifiable->can('view', $this->notice->caseFile);
    }
}
