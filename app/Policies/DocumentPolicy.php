<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->can('view', $document->event);
    }

    /**
     * Determine whether the user can create models.
     */
    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
