<?php

namespace App\Policies;

use App\CaseAssignmentRole;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
        if ($user->isManager()) {
            return true;
        }

        if (! $user->isLawyer()) {
            return false;
        }

        if (! $party->activeCaseFiles()->exists()) {
            return $party->created_by === $user->id;
        }

        return ! $party->activeCaseFiles()
            ->whereDoesntHave('assignments', fn (Builder $assignments): Builder => $assignments
                ->where('lawyer_id', $user->id)
                ->where('role', CaseAssignmentRole::Lead->value)
                ->whereNull('ended_at'))
            ->exists();
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
