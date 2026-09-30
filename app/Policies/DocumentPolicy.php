<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isLegalWorker();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, Document $document): bool
    {
        return match (true) {
            $document->case_file_id !== null => $user->can('viewContent', $document->caseFile),
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
        return $user->isLegalWorker()
            && $this->viewUdf($user, $document)
            && $user->can('manageDocuments', $document->caseFile);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return ($user->isManager() || $user->isLegalWorker())
            && $this->view($user, $document)
            && (! $user->isAssistant() || $document->case_file_id === null || $user->can('manageDocuments', $document->caseFile));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
