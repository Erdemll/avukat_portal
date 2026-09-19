<?php

namespace App\Services\Udf;

use App\AuditAction;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UdfDocumentService
{
    public function __construct(
        private UdfArchiveService $archives,
        private UdfParser $parser,
        private UdfSerializer $serializer,
        private UdfContentValidator $validator,
        private UdfSignatureInspector $signatures,
        private AuditService $audit,
    ) {}

    /**
     * @return array{content: array<string, mixed>, compatibility: string, unsupported_nodes: list<string>, format_id: ?string, signature_status: string, version: DocumentVersion}
     */
    public function open(Document $document): array
    {
        $version = $document->currentVersion()->with('uploader')->first();
        if ($version === null) {
            throw new UdfException('Bu UDF belgesinin okunabilir bir sürümü bulunmuyor.', 'version_missing');
        }

        $archive = $this->archives->read($version->disk, $version->path);
        $parsed = $this->parser->parse($archive['content_xml']);
        $signatureStatus = $this->signatures->inspect($archive['entries'], $archive['content_xml']);

        if ($signatureStatus === 'signed_or_signature_detected'
            && $version->change_note === 'UDF editörü ile yeni sürüm oluşturuldu.') {
            $signatureStatus = 'signature_invalidated_by_edit';
        }

        return [
            ...$parsed,
            'signature_status' => $signatureStatus,
            'version' => $version,
        ];
    }

    /** @param array<string, mixed> $content */
    public function createVersion(Document $document, int $expectedVersion, array $content, User $actor): DocumentVersion
    {
        $this->validator->validate($content);
        $sourceVersion = $document->currentVersion()->first();

        if ($sourceVersion === null || $sourceVersion->version_no !== $expectedVersion) {
            throw UdfException::conflict();
        }

        $archive = $this->archives->read($sourceVersion->disk, $sourceVersion->path);
        $parsed = $this->parser->parse($archive['content_xml']);

        if ($parsed['compatibility'] === 'unsupported') {
            throw new UdfException('Bu UDF yapısı güvenli düzenleme için desteklenmiyor. Belgeyi yalnız görüntüleyebilir ve indirebilirsiniz.', 'unsupported_structure');
        }

        $xml = $this->serializer->serialize($content, $archive['content_xml']);
        $udfContents = $this->archives->buildEditedCopy($sourceVersion->disk, $sourceVersion->path, $xml);
        $storedName = Str::uuid().'.udf';
        $path = 'case-files/'.$document->case_file_id.'/'.$storedName;
        $stored = false;

        try {
            return DB::transaction(function () use ($document, $expectedVersion, $actor, $udfContents, $storedName, $path, &$stored): DocumentVersion {
                $lockedDocument = Document::query()->lockForUpdate()->findOrFail($document->id);
                $currentVersion = $lockedDocument->versions()->lockForUpdate()->latest('version_no')->first();

                if ($currentVersion === null || $currentVersion->version_no !== $expectedVersion) {
                    throw UdfException::conflict();
                }

                if (! Storage::disk('legal_private')->put($path, $udfContents)) {
                    throw new RuntimeException('Yeni UDF sürümü özel depolama alanına yazılamadı.');
                }
                $stored = true;

                $nextVersion = $expectedVersion + 1;
                $metadata = [
                    'original_name' => $lockedDocument->original_name,
                    'stored_name' => $storedName,
                    'disk' => 'legal_private',
                    'path' => $path,
                    'mime_type' => 'application/zip',
                    'extension' => 'udf',
                    'size' => strlen($udfContents),
                    'sha256' => hash('sha256', $udfContents),
                ];

                $version = new DocumentVersion(['change_note' => 'UDF editörü ile yeni sürüm oluşturuldu.']);
                $version->forceFill([
                    ...$metadata,
                    'document_id' => $lockedDocument->id,
                    'version_no' => $nextVersion,
                    'uploaded_by' => $actor->id,
                ])->save();

                $lockedDocument->forceFill([
                    'original_name' => $metadata['original_name'],
                    'stored_name' => $metadata['stored_name'],
                    'disk' => $metadata['disk'],
                    'path' => $metadata['path'],
                    'mime_type' => $metadata['mime_type'],
                    'extension' => $metadata['extension'],
                    'size' => $metadata['size'],
                ])->save();
                $this->audit->log(
                    AuditAction::UdfVersionCreated,
                    $actor,
                    auditable: $version,
                    description: 'UDF belgesi için yeni sürüm oluşturuldu.',
                    newValues: ['document_id' => $lockedDocument->id, 'version_no' => $nextVersion, 'sha256' => $metadata['sha256']],
                    caseFile: $lockedDocument->caseFile,
                );

                return $version;
            });
        } catch (Throwable $exception) {
            if ($stored) {
                try {
                    Storage::disk('legal_private')->delete($path);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($exception instanceof UdfException) {
                throw $exception;
            }

            report($exception);

            throw new UdfException(
                'Yeni UDF sürümü güvenli biçimde kaydedilemedi.',
                'version_storage_failed',
                500,
                $exception,
            );
        }
    }
}
