<?php

namespace App\Services;

use App\AuditAction;
use App\EventPriority;
use App\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventManagementService
{
    public function __construct(private AuditService $audit, private EventNotificationService $notifications) {}

    public function changeStatus(Event $event, EventStatus $status, User $actor): void
    {
        DB::transaction(function () use ($event, $status, $actor): void {
            $old = $event->system_status;
            $event->forceFill(['system_status' => $status, 'closed_at' => $status === EventStatus::Closed ? now() : null])->save();
            $this->audit->log($status === EventStatus::Closed ? AuditAction::EventClosed : AuditAction::EventStatusChanged, $actor, $event, $event, 'Olay durumu değiştirildi.', ['system_status' => $old->value], ['system_status' => $status->value]);
            if ($status === EventStatus::Closed) {
                DB::afterCommit(fn () => $this->notifications->closed($event->load(['creator', 'assignedLawyer']), $actor));
            }
        });
    }

    public function changePriority(Event $event, EventPriority $priority, User $actor): void
    {
        DB::transaction(function () use ($event, $priority, $actor): void {
            $old = $event->priority;
            $event->forceFill(['priority' => $priority])->save();
            $this->audit->log(AuditAction::EventPriorityChanged, $actor, $event, $event, 'Olay önceliği değiştirildi.', ['priority' => $old->value], ['priority' => $priority->value]);
        });
    }

    public function reassign(Event $event, User $lawyer, User $actor): void
    {
        DB::transaction(function () use ($event, $lawyer, $actor): void {
            $lawyer = User::query()->lockForUpdate()->findOrFail($lawyer->id);
            if (! $lawyer->is_active || ! $lawyer->isLawyer()) {
                throw ValidationException::withMessages(['assigned_lawyer_id' => 'Atama yalnız aktif bir avukata yapılabilir.']);
            }
            $old = $event->assigned_lawyer_id;
            $event->forceFill(['assigned_lawyer_id' => $lawyer->id, 'assigned_at' => now()])->save();
            $this->audit->log(AuditAction::EventReassigned, $actor, $event, $event, 'Avukat ataması değiştirildi.', ['assigned_lawyer_id' => $old], ['assigned_lawyer_id' => $lawyer->id]);
            if (! $event->trashed()) {
                DB::afterCommit(fn () => $this->notifications->assigned($event->load('assignedLawyer'), $actor));
            }
        });
    }
}
