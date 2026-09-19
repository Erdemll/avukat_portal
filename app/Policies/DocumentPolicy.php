<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isLawyer();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, Document $document): bool
    {
        return match (true) {
            $document->case_file_id !== null => $user->can('view', $document->caseFile),
            $document->event_id !== null => $user->can('view', $document->event),
            default => false,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function uploadVersion(User $user, Document $document): bool
    {
        if ($document->isUdf()) {
            return $this->editUdf($user, $document);
        }

        return $document->case_file_id !== null && $user->can('manageDocuments', $document->caseFile);
    }

    public function viewUdf(User $user, Document $document): bool
    {
        return $document->case_file_id !== null
            && $document->isUdf()
            && $this->view($user, $document);
    }

    public function editUdf(User $user, Document $document): bool
    {
        return $user->isLawyer()
            && $this->viewUdf($user, $document)
            && $user->can('manageDocuments', $document->caseFile);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->isManager() && $this->view($user, $document);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
