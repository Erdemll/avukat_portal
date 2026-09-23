<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
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
    public function view(User $user, Client $client): bool
    {
        return $user->isManager() || ($user->isLawyer() && (
            ($client->created_by === $user->id && ($client->responsible_lawyer_id === null || $client->responsible_lawyer_id === $user->id))
            || $user->can('view', $client->party)
        ));
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
    public function update(User $user, Client $client): bool
    {
        return $this->view($user, $client) && $user->can('update', $client->party);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->isManager() || ($user->isLawyer() && (
            ($client->created_by === $user->id && ($client->responsible_lawyer_id === null || $client->responsible_lawyer_id === $user->id))
            || ($client->responsible_lawyer_id === $user->id && ! $client->party->activeCaseFiles()->exists())
            || $client->party->caseFiles()->visibleTo($user)->exists()
        ));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return false;
    }
}
