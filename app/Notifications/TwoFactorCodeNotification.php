<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hukuk Portalı giriş doğrulama kodunuz')
            ->greeting('Merhaba,')
            ->line('Giriş doğrulama kodunuz: '.$this->code)
            ->line('Bu kod 10 dakika boyunca geçerlidir. Kodu siz istemediyseniz hesabınızın şifresini değiştirin.')
            ->salutation('Tepenet Hukuk Portalı');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
