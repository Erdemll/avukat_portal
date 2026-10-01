<?php

namespace App\Policies;

use App\Models\CaseFile;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isEmployee() || $user->isLegalWorker();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->isManager() || ($user->isEmployee() && $event->created_by === $user->id)
            || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id)
            || ($user->isAssistant() && $event->caseFiles()->visibleTo($user)->exists());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isEmployee();
    }

    public function createUpdate(User $user, Event $event): bool
    {
        return $user->isManager() || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id)
            || $this->canManageLinkedCase($user, $event);
    }

    public function createDocument(User $user, Event $event): bool
    {
        return $user->isManager()
            || ($user->isEmployee() && $event->created_by === $user->id)
            || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id)
            || $this->canManageLinkedCase($user, $event);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->isManager() || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id)
            || $this->canManageLinkedCase($user, $event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->isManager() || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id)
            || $this->canManageLinkedCase($user, $event);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    private function canManageLinkedCase(User $user, Event $event): bool
    {
        return $user->isAssistant()
            && $event->caseFiles()->visibleTo($user)->exists()
            && ! $event->caseFiles()->whereNotIn('case_files.id', CaseFile::query()->visibleTo($user)->select('case_files.id'))->exists();
    }
}
