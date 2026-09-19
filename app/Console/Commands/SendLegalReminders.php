<?php

namespace App\Console\Commands;

use App\DeadlineStatus;
use App\HearingStatus;
use App\Models\Deadline;
use App\Models\Hearing;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use App\Notifications\HearingReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

#[Signature('legal:send-reminders')]
#[Description('Yaklaşan duruşma ve hukuki süreler için tek seferlik bildirim gönderir.')]
class SendLegalReminders extends Command
{
    public function handle(): int
    {
        $sentHearings = 0;
        $sentDeadlines = 0;

        Hearing::query()
            ->with(['caseFile.activeLawyers', 'lawyer'])
            ->where('status', HearingStatus::Scheduled)
            ->whereNull('reminder_sent_at')
            ->whereBetween('hearing_at', [now(), now()->addHours((int) config('legal.reminders.hearing_hours', 48))])
            ->chunkById(100, function ($hearings) use (&$sentHearings): void {
                foreach ($hearings as $hearing) {
                    $recipients = $this->recipients($hearing->lawyer, $hearing->caseFile->activeLawyers);
                    if ($recipients->isEmpty()) {
                        continue;
                    }
                    Notification::send($recipients, new HearingReminderNotification($hearing));
                    $hearing->forceFill(['reminder_sent_at' => now()])->save();
                    $sentHearings++;
                }
            });

        Deadline::query()
            ->with(['caseFile.activeLawyers', 'assignedLawyer'])
            ->where('status', DeadlineStatus::Open)
            ->whereNull('reminder_sent_at')
            ->whereBetween('due_at', [now(), now()->addHours((int) config('legal.reminders.deadline_hours', 72))])
            ->chunkById(100, function ($deadlines) use (&$sentDeadlines): void {
                foreach ($deadlines as $deadline) {
                    $recipients = $this->recipients($deadline->assignedLawyer, $deadline->caseFile->activeLawyers);
                    if ($recipients->isEmpty()) {
                        continue;
                    }
                    Notification::send($recipients, new DeadlineReminderNotification($deadline));
                    $deadline->forceFill(['reminder_sent_at' => now()])->save();
                    $sentDeadlines++;
                }
            });

        $this->info("{$sentHearings} duruşma ve {$sentDeadlines} süre hatırlatması kuyruğa alındı.");

        return self::SUCCESS;
    }

    /** @param Collection<int, User> $fallback */
    private function recipients(?User $primary, Collection $fallback): Collection
    {
        return collect([$primary])->concat($fallback)
            ->filter(fn (?User $user): bool => $user !== null && $user->is_active)
            ->unique('id')
            ->values();
    }
}
