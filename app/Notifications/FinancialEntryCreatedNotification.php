<?php

namespace App\Notifications;

use App\Models\CaseFinancialEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinancialEntryCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private CaseFinancialEntry $entry)
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
        return (new MailMessage)->subject('Yeni dosya finans hareketi')
            ->line($this->entry->caseFile->case_no.' · '.$this->entry->type->label().' '.$this->entry->amount.' '.$this->entry->currency)
            ->action('Finans Hareketlerini Görüntüle', route('financial-entries.index', ['case_file' => $this->entry->case_file_id]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'financial_entry_created', 'case_file_id' => $this->entry->case_file_id, 'message' => 'Yeni dosya finans hareketi oluşturuldu.', 'url' => route('financial-entries.index', ['case_file' => $this->entry->case_file_id])];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof User && $notifiable->is_active && $notifiable->can('view', $this->entry->caseFile);
    }
}
