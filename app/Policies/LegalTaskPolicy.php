<?php

namespace App\Policies;

use App\Models\LegalTask;
use App\Models\User;

class LegalTaskPolicy
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
    public function view(User $user, LegalTask $legalTask): bool
    {
        return $user->isManager() || ($legalTask->case_file_id !== null
            ? $user->can('view', $legalTask->caseFile)
            : $legalTask->assigned_to === $user->id);
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
    public function update(User $user, LegalTask $legalTask): bool
    {
        return $user->isManager() || ($legalTask->case_file_id !== null
            ? $user->can('manageLegalOperations', $legalTask->caseFile)
            : $legalTask->assigned_to === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LegalTask $legalTask): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LegalTask $legalTask): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LegalTask $legalTask): bool
    {
        return false;
    }
}
