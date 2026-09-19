<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\UpdateUdfDocumentRequest;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\Udf\UdfDocumentService;
use App\Services\Udf\UdfException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UdfDocumentController extends Controller
{
    public function show(Request $request, Document $document, UdfDocumentService $udf, AuditService $audit): View|Response
    {
        Gate::authorize('viewUdf', $document);
        $document->loadMissing(['caseFile', 'uploader']);

        try {
            $data = $udf->open($document);
        } catch (UdfException $exception) {
            $this->auditParseFailure($request, $document, $exception, $audit);

            return response()->view('documents.udf.error', compact('document', 'exception'), $exception->status);
        }

        $audit->safelyLog(
            AuditAction::UdfViewed,
            $request->user(),
            auditable: $data['version'],
            description: 'UDF belgesi görüntülendi.',
            newValues: ['document_id' => $document->id, 'version_no' => $data['version']->version_no],
            caseFile: $document->caseFile,
        );

        return view('documents.udf.show', compact('document', 'data'));
    }

    public function edit(Request $request, Document $document, UdfDocumentService $udf, AuditService $audit): View|Response
    {
        Gate::authorize('editUdf', $document);
        $document->loadMissing(['caseFile', 'uploader']);

        try {
            $data = $udf->open($document);

            if ($data['compatibility'] === 'unsupported') {
                throw new UdfException('Bu UDF yapısı güvenli düzenleme için desteklenmiyor. Belgeyi görüntüleyebilir veya indirebilirsiniz.', 'unsupported_structure');
            }
        } catch (UdfException $exception) {
            $this->auditParseFailure($request, $document, $exception, $audit);

            return response()->view('documents.udf.error', compact('document', 'exception'), $exception->status);
        }

        $audit->safelyLog(
            AuditAction::UdfEditStarted,
            $request->user(),
            auditable: $data['version'],
            description: 'UDF düzenleme ekranı açıldı.',
            newValues: ['document_id' => $document->id, 'version_no' => $data['version']->version_no],
            caseFile: $document->caseFile,
        );

        return view('documents.udf.edit', compact('document', 'data'));
    }

    public function update(UpdateUdfDocumentRequest $request, Document $document, UdfDocumentService $udf, AuditService $audit): JsonResponse
    {
        try {
            $version = $udf->createVersion(
                $document,
                $request->integer('document_version'),
                $request->input('content'),
                $request->user(),
            );
        } catch (UdfException $exception) {
            if ($exception->reason !== 'version_conflict') {
                $this->auditParseFailure($request, $document, $exception, $audit);
            }

            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json([
            'message' => 'UDF belgesinin yeni sürümü oluşturuldu.',
            'version' => $version->version_no,
            'redirect' => route('documents.udf.show', $document),
        ]);
    }

    public function download(Request $request, Document $document, AuditService $audit): StreamedResponse
    {
        Gate::authorize('viewUdf', $document);
        $version = $document->currentVersion()->firstOrFail();

        $audit->safelyLog(
            AuditAction::UdfDownloaded,
            $request->user(),
            auditable: $version,
            description: 'UDF belgesi indirildi.',
            newValues: ['document_id' => $document->id, 'version_no' => $version->version_no],
            caseFile: $document->caseFile,
        );

        return Storage::disk($version->disk)->download($version->path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function auditParseFailure(Request $request, Document $document, UdfException $exception, AuditService $audit): void
    {
        $audit->safelyLog(
            AuditAction::UdfParseFailed,
            $request->user(),
            auditable: $document,
            description: 'UDF belgesi güvenli biçimde işlenemedi.',
            newValues: [
                'document_id' => $document->id,
                'version_no' => $document->currentVersion()->value('version_no'),
                'reason' => $exception->reason,
            ],
            caseFile: $document->caseFile,
        );
    }
}
