<?php

namespace App\Services;

use App\Models\CaseFile;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\CaseDocumentsUploadedNotification;
use App\Notifications\CaseDocumentVersionUploadedNotification;
use App\Notifications\CaseFileAssignedNotification;
use App\Notifications\LegalActivityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CaseFileNotificationService
{
    /** @param Collection<int, User> $lawyers */
    public function assigned(CaseFile $caseFile, Collection $lawyers, User $actor): void
    {
        $this->send($lawyers, new CaseFileAssignedNotification($caseFile), $actor);
    }

    /**
     * @param  array<int, string>  $documentNames
     */
    public function documentsUploaded(CaseFile $caseFile, array $documentNames, User $actor): void
    {
        if ($documentNames === []) {
            return;
        }

        $this->send(
            $caseFile->activeLawyers()->get(),
            new CaseDocumentsUploadedNotification($caseFile, $documentNames),
            $actor,
        );
    }

    public function documentVersionUploaded(DocumentVersion $version, User $actor): void
    {
        $caseFile = $version->document->caseFile;

        $this->send(
            $caseFile->activeLawyers()->get(),
            new CaseDocumentVersionUploadedNotification($version),
            $actor,
        );
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function send(Collection $recipients, LegalActivityNotification $notification, User $actor): void
    {
        $recipients = $recipients
            ->filter(fn (User $lawyer): bool => $lawyer->is_active && $lawyer->id !== $actor->id)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, $notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
