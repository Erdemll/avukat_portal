<?php

namespace App\Services;

use App\Models\CaseAssignmentRequest;
use App\Models\CaseFinancialEntry;
use App\Models\ServiceNotice;
use App\Models\User;
use App\Notifications\CaseAssignmentRequestCreatedNotification;
use App\Notifications\CaseAssignmentRequestDecidedNotification;
use App\Notifications\FinancialEntryCreatedNotification;
use App\Notifications\ServiceNoticeCreatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CaseWorkflowNotificationService
{
    public function assignmentRequestCreated(CaseAssignmentRequest $request, User $actor): void
    {
        $this->send(collect($this->managers()->all())->push($request->requestedTo), new CaseAssignmentRequestCreatedNotification($request), $actor);
    }

    public function assignmentRequestDecided(CaseAssignmentRequest $request, User $actor): void
    {
        $this->send(collect([$request->requester, $request->requestedTo]), new CaseAssignmentRequestDecidedNotification($request), $actor);
    }

    public function serviceNoticeCreated(ServiceNotice $notice, User $actor): void
    {
        $this->send(collect($notice->caseFile->activeLawyers->all())->concat($this->managers()), new ServiceNoticeCreatedNotification($notice), $actor);
    }

    public function financialEntryCreated(CaseFinancialEntry $entry, User $actor): void
    {
        $this->send($entry->caseFile->activeLawyers, new FinancialEntryCreatedNotification($entry), $actor);
    }

    private function managers(): Collection
    {
        return User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'manager'))->get();
    }

    private function send(Collection $recipients, object $notification, User $actor): void
    {
        $recipients = $recipients->filter(fn (?User $user): bool => $user !== null && $user->is_active && $user->id !== $actor->id)->unique('id')->values();
        if ($recipients->isEmpty()) {
            return;
        }
        try {
            Notification::send($recipients, $notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
