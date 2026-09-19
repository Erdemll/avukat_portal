<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\StoreDocumentVersionRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditService;
use App\Services\CaseDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function store(StoreDocumentVersionRequest $request, Document $document, CaseDocumentService $documents): RedirectResponse
    {
        $documents->addVersion($document, $request->file('file'), $request->user(), $request->string('change_note')->toString() ?: null);

        return back()->with('success', 'Yeni evrak sürümü yüklendi.');
    }

    public function download(DocumentVersion $documentVersion, AuditService $audit): StreamedResponse
    {
        Gate::authorize('view', $documentVersion);
        $document = $documentVersion->document;
        $audit->safelyLog(AuditAction::DocumentDownloaded, auth()->user(), $document->event, $documentVersion, 'Evrak sürümü indirildi.', [], ['document_id' => $documentVersion->document_id, 'version_no' => $documentVersion->version_no], $document->caseFile);

        return Storage::disk($documentVersion->disk)->download($documentVersion->path, $documentVersion->original_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview(DocumentVersion $documentVersion): StreamedResponse
    {
        Gate::authorize('view', $documentVersion);
        abort_unless(in_array($documentVersion->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);

        return Storage::disk($documentVersion->disk)->response($documentVersion->path, $documentVersion->original_name, [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'; img-src data:",
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
