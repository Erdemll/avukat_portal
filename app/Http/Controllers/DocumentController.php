<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Event;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\EventNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Event $event, DocumentService $documents, AuditService $audit, EventNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('createDocument', $event);
        $storedDocuments = collect();

        try {
            DB::transaction(function () use ($request, $event, $documents, $audit, $storedDocuments): void {
                foreach ($request->file('documents') as $file) {
                    $document = $documents->storeForEvent($event, $file, $request->user());
                    $storedDocuments->push($document);
                    $audit->log(AuditAction::DocumentUploaded, $request->user(), $event, $document, 'Belge yüklendi.', [], ['original_name' => $document->original_name, 'mime_type' => $document->mime_type, 'size' => $document->size, 'event_update_id' => $document->event_update_id]);
                }
            });
        } catch (Throwable $exception) {
            $documents->deleteStoredFiles($storedDocuments);

            throw $exception;
        }

        foreach ($storedDocuments as $document) {
            $notifications->documentUploaded($document->load('event.creator', 'event.assignedLawyer'), $request->user());
        }

        return redirect()->route('events.show', $event);
    }

    public function download(Document $document, AuditService $audit): StreamedResponse
    {
        Gate::authorize('download', $document);
        $version = $document->currentVersion;
        $audit->safelyLog(
            AuditAction::DocumentDownloaded,
            auth()->user(),
            $document->event,
            $version ?? $document,
            'Belge indirildi.',
            [],
            ['original_name' => $version?->original_name ?? $document->original_name, 'version_no' => $version?->version_no],
            $document->caseFile,
        );

        return Storage::disk($version?->disk ?? $document->disk)
            ->download($version?->path ?? $document->path, $version?->original_name ?? $document->original_name, [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }

    public function destroy(Document $document, AuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $event = $document->event;
        $caseFile = $document->caseFile;
        DB::transaction(function () use ($document, $audit, $event, $caseFile): void {
            $document->delete();
            $audit->log(AuditAction::DocumentDeleted, auth()->user(), $event, $document, 'Belge arşivlendi.', [], ['original_name' => $document->original_name], $caseFile);
        });

        return $caseFile !== null
            ? redirect()->route('case-files.show', $caseFile)
            : redirect()->route('events.show', $event);
    }
}
