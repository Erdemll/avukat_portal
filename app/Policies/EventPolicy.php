<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isEmployee() || $user->isLawyer();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->isManager() || ($user->isEmployee() && $event->created_by === $user->id) || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id);
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
        return $user->isManager() || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id);
    }

    public function createDocument(User $user, Event $event): bool
    {
        return $user->isManager()
            || ($user->isEmployee() && $event->created_by === $user->id)
            || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->isManager() || ($user->isLawyer() && $event->assigned_lawyer_id === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->isManager();
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
}
