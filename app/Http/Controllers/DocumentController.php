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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Event $event, DocumentService $documents, AuditService $audit, EventNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('view', $event);
        foreach ($request->file('documents') as $file) {
            $document = $documents->storeForEvent($event, $file, $request->user());
            $audit->safelyLog(AuditAction::DocumentUploaded, $request->user(), $event, $document, 'Belge yüklendi.', [], ['original_name' => $document->original_name, 'mime_type' => $document->mime_type, 'size' => $document->size, 'event_update_id' => $document->event_update_id]);
            $notifications->documentUploaded($document->load('event.creator', 'event.assignedLawyer'), $request->user());
        }

        return redirect()->route('events.show', $event);
    }

    public function download(Document $document, AuditService $audit): StreamedResponse
    {
        Gate::authorize('download', $document);
        $audit->safelyLog(AuditAction::DocumentDownloaded, auth()->user(), $document->event, $document, 'Belge indirildi.', [], ['original_name' => $document->original_name]);

        return Storage::disk($document->disk)
            ->download($document->path, $document->original_name, [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }

    public function destroy(Document $document, AuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $document->delete();
        $audit->safelyLog(AuditAction::DocumentDeleted, auth()->user(), $document->event, $document, 'Belge silindi.', [], ['original_name' => $document->original_name]);

        return redirect()->route('events.show', $document->event);
    }
}
