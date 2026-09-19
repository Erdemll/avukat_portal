<?php

namespace App\Policies;

use App\Models\ClientCommunication;
use App\Models\User;

class ClientCommunicationPolicy
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
    public function view(User $user, ClientCommunication $clientCommunication): bool
    {
        return $user->isManager() || ($user->can('view', $clientCommunication->client)
            && ($clientCommunication->caseFile === null ? $clientCommunication->user_id === $user->id : $user->can('view', $clientCommunication->caseFile)));
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
    public function update(User $user, ClientCommunication $clientCommunication): bool
    {
        return $this->view($user, $clientCommunication) && ($user->isManager() || $clientCommunication->user_id === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClientCommunication $clientCommunication): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClientCommunication $clientCommunication): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ClientCommunication $clientCommunication): bool
    {
        return false;
    }
}
