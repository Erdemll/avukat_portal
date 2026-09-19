<?php

namespace App\Services;

use App\Models\CaseFile;
use App\Models\User;
use App\Notifications\CaseFileAssignedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CaseFileNotificationService
{
    /** @param Collection<int, User> $lawyers */
    public function assigned(CaseFile $caseFile, Collection $lawyers, User $actor): void
    {
        $recipients = $lawyers
            ->filter(fn (User $lawyer): bool => $lawyer->is_active && $lawyer->id !== $actor->id)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, new CaseFileAssignedNotification($caseFile));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
