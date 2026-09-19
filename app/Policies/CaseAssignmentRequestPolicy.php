<?php

namespace App\Policies;

use App\Models\CaseAssignmentRequest;
use App\Models\User;

class CaseAssignmentRequestPolicy
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
    public function view(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return $user->isManager() || $caseAssignmentRequest->requested_by === $user->id || $caseAssignmentRequest->requested_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isLawyer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return false;
    }

    public function review(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return $user->isManager() && $caseAssignmentRequest->status->value === 'pending';
    }

    public function cancel(User $user, CaseAssignmentRequest $caseAssignmentRequest): bool
    {
        return $caseAssignmentRequest->requested_by === $user->id && $caseAssignmentRequest->status->value === 'pending';
    }
}
