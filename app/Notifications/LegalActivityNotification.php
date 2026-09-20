<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class LegalActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @return array<int, string>
     */
    final public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof User && $notifiable->is_active;
    }
}
