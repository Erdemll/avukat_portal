<?php

namespace App\Policies;

use App\Models\CaseFile;
use App\Models\User;

class CaseFilePolicy
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
    public function view(User $user, CaseFile $caseFile): bool
    {
        return $user->isManager() || ($user->isLawyer() && $caseFile->assignments()
            ->where('lawyer_id', $user->id)
            ->whereNull('ended_at')
            ->exists());
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
    public function update(User $user, CaseFile $caseFile): bool
    {
        return $this->view($user, $caseFile);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CaseFile $caseFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CaseFile $caseFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CaseFile $caseFile): bool
    {
        return false;
    }

    public function assign(User $user, CaseFile $caseFile): bool
    {
        return $user->isManager();
    }

    public function manageParties(User $user, CaseFile $caseFile): bool
    {
        return $this->update($user, $caseFile);
    }

    public function manageDocuments(User $user, CaseFile $caseFile): bool
    {
        return $this->update($user, $caseFile);
    }

    public function manageLegalOperations(User $user, CaseFile $caseFile): bool
    {
        return $this->update($user, $caseFile);
    }
}
