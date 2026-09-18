<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Event;
use App\Models\EventUpdate;
use App\Models\User;
use App\Notifications\DocumentUploadedNotification;
use App\Notifications\EventAssignedNotification;
use App\Notifications\EventClosedNotification;
use App\Notifications\EventUpdatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class EventNotificationService
{
    public function assigned(Event $event, User $actor): void
    {
        $this->send(collect([$event->assignedLawyer]), new EventAssignedNotification($event), $actor);
    }

    public function updated(EventUpdate $update, User $actor): void
    {
        $this->send($this->managers()->push($update->event->creator), new EventUpdatedNotification($update), $actor);
    }

    public function documentUploaded(Document $document, User $actor): void
    {
        $event = $document->event;
        $recipients = match (true) {
            $actor->isEmployee() => $this->managers()->push($event->assignedLawyer),
            $actor->isLawyer() => $this->managers()->push($event->creator),
            default => collect([$event->creator, $event->assignedLawyer]),
        };
        $this->send($recipients, new DocumentUploadedNotification($document), $actor);
    }

    public function closed(Event $event, User $actor): void
    {
        $this->send($this->managers()->merge([$event->creator, $event->assignedLawyer]), new EventClosedNotification($event), $actor);
    }

    private function managers(): Collection
    {
        return User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'manager'))->get();
    }

    private function send(Collection $recipients, object $notification, User $actor): void
    {
        $users = $recipients->filter(fn (?User $user): bool => $user !== null && $user->is_active && $user->id !== $actor->id)->unique('id')->values();
        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, $notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
