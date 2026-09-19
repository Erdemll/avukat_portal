<?php

namespace App\Policies;

use App\Models\Deadline;
use App\Models\User;

class DeadlinePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isLawyer();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deadline $deadline): bool
    {
        return $user->can('view', $deadline->caseFile);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isLawyer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deadline $deadline): bool
    {
        return $user->can('manageLegalOperations', $deadline->caseFile);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deadline $deadline): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Deadline $deadline): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Deadline $deadline): bool
    {
        return false;
    }
}
