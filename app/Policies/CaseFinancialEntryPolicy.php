<?php

namespace App\Policies;

use App\Models\CaseFinancialEntry;
use App\Models\User;

class CaseFinancialEntryPolicy
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
    public function view(User $user, CaseFinancialEntry $caseFinancialEntry): bool
    {
        return $user->can('view', $caseFinancialEntry->caseFile);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CaseFinancialEntry $caseFinancialEntry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CaseFinancialEntry $caseFinancialEntry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CaseFinancialEntry $caseFinancialEntry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CaseFinancialEntry $caseFinancialEntry): bool
    {
        return false;
    }

    public function reverse(User $user, CaseFinancialEntry $caseFinancialEntry): bool
    {
        return $user->isManager() && $caseFinancialEntry->reversal_of_id === null && ! $caseFinancialEntry->reversal()->exists();
    }
}
