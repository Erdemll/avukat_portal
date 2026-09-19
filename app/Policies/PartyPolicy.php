<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;

class PartyPolicy
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
    public function view(User $user, Party $party): bool
    {
        return $user->isManager() || ($user->isLawyer() && ($party->created_by === $user->id || $party->activeCaseFiles()->visibleTo($user)->exists()));
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
    public function update(User $user, Party $party): bool
    {
        return $user->isManager() || ($user->isLawyer() && ($party->created_by === $user->id || $party->activeCaseFiles()->visibleTo($user)->exists()));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Party $party): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Party $party): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Party $party): bool
    {
        return false;
    }

    public function viewIdentifiers(User $user, Party $party): bool
    {
        return $user->isManager();
    }
}
