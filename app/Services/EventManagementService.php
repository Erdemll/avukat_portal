<?php

namespace App\Services;

use App\AuditAction;
use App\EventPriority;
use App\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EventManagementService
{
    public function __construct(private AuditService $audit, private EventNotificationService $notifications) {}

    public function changeStatus(Event $event, EventStatus $status, User $actor): void
    {
        $old = $event->system_status;
        $event->forceFill(['system_status' => $status, 'closed_at' => $status === EventStatus::Closed ? now() : null])->save();
        $this->audit->safelyLog($status === EventStatus::Closed ? AuditAction::EventClosed : AuditAction::EventStatusChanged, $actor, $event, $event, 'Olay durumu değiştirildi.', ['system_status' => $old->value], ['system_status' => $status->value]);
        if ($status === EventStatus::Closed) {
            $this->notifications->closed($event->load(['creator', 'assignedLawyer']), $actor);
        }
    }

    public function changePriority(Event $event, EventPriority $priority, User $actor): void
    {
        $old = $event->priority;
        $event->forceFill(['priority' => $priority])->save();
        $this->audit->safelyLog(AuditAction::EventPriorityChanged, $actor, $event, $event, 'Olay önceliği değiştirildi.', ['priority' => $old->value], ['priority' => $priority->value]);
    }

    public function reassign(Event $event, User $lawyer, User $actor): void
    {
        DB::transaction(function () use ($event, $lawyer, $actor): void {
            $old = $event->assigned_lawyer_id;
            $event->forceFill(['assigned_lawyer_id' => $lawyer->id, 'assigned_at' => now()])->save();
            $this->audit->safelyLog(AuditAction::EventReassigned, $actor, $event, $event, 'Avukat ataması değiştirildi.', ['assigned_lawyer_id' => $old], ['assigned_lawyer_id' => $lawyer->id]);
            DB::afterCommit(fn () => $this->notifications->assigned($event->load('assignedLawyer'), $actor));
        });
    }
}
