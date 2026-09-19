<?php

namespace App\Policies;

use App\Models\Mediation;
use App\Models\User;

class MediationPolicy
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
    public function view(User $user, Mediation $mediation): bool
    {
        return $user->can('view', $mediation->caseFile);
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
    public function update(User $user, Mediation $mediation): bool
    {
        return $user->can('manageLegalOperations', $mediation->caseFile);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Mediation $mediation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Mediation $mediation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Mediation $mediation): bool
    {
        return false;
    }
}
