<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CaseDocumentService
{
    public function __construct(private AuditService $audit) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $metadata
     * @return Collection<int, Document>
     */
    public function storeMany(CaseFile $caseFile, array $files, array $metadata, User $actor): Collection
    {
        $storedPaths = collect();

        try {
            return DB::transaction(function () use ($caseFile, $files, $metadata, $actor, $storedPaths): Collection {
                $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
                Gate::forUser($actor)->authorize('manageDocuments', $caseFile);
                if ($caseFile->status->value === 'closed') {
                    throw ValidationException::withMessages(['documents' => 'Kapalı dosyaya evrak yüklenemez.']);
                }
                $documents = new Collection;

                foreach ($files as $file) {
                    $fileMetadata = $this->storeFile($caseFile, $file);
                    $storedPaths->push($fileMetadata);
                    $document = new Document([
                        ...Arr::except($fileMetadata, ['sha256']),
                        'title' => count($files) === 1 && ! empty($metadata['title'])
                            ? $metadata['title']
                            : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'document_type' => $metadata['document_type'] ?? 'other',
                    ]);
                    $document->case_file_id = $caseFile->id;
                    $document->folder_id = $metadata['folder_id'] ?? null;
                    $document->uploaded_by = $actor->id;
                    $document->save();

                    $version = $this->createVersion($document, $fileMetadata, $actor, 1, 'İlk sürüm');
                    $this->audit->log(AuditAction::DocumentUploaded, $actor, auditable: $document, description: 'Hukuki dosyaya evrak yüklendi.', newValues: ['title' => $document->title, 'document_type' => $document->document_type, 'version_no' => 1, 'sha256' => $version->sha256], caseFile: $caseFile);
                    $documents->push($document->load('currentVersion'));
                }

                return $documents;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $fileMetadata) {
                Storage::disk($fileMetadata['disk'])->delete($fileMetadata['path']);
            }

            throw $exception;
        }
    }

    public function addVersion(Document $document, UploadedFile $file, User $actor, ?string $changeNote): DocumentVersion
    {
        $caseFile = $document->caseFile;
        $fileMetadata = $this->storeFile($caseFile, $file);

        try {
            return DB::transaction(function () use ($document, $fileMetadata, $actor, $changeNote): DocumentVersion {
                $document = Document::query()->lockForUpdate()->findOrFail($document->id);
                Gate::forUser($actor)->authorize('uploadVersion', $document);
                if ($document->caseFile->status->value === 'closed') {
                    throw ValidationException::withMessages(['file' => 'Kapalı dosyaya yeni evrak sürümü yüklenemez.']);
                }
                $nextVersion = ((int) $document->versions()->max('version_no')) + 1;
                $version = $this->createVersion($document, $fileMetadata, $actor, $nextVersion, $changeNote);
                $document->forceFill(Arr::except($fileMetadata, ['sha256']))->save();
                $this->audit->log(AuditAction::DocumentVersionUploaded, $actor, auditable: $version, description: 'Yeni evrak sürümü yüklendi.', newValues: ['document_id' => $document->id, 'version_no' => $nextVersion, 'sha256' => $version->sha256], caseFile: $document->caseFile);

                return $version;
            });
        } catch (Throwable $exception) {
            Storage::disk($fileMetadata['disk'])->delete($fileMetadata['path']);
            throw $exception;
        }
    }

    /** @return array{original_name: string, stored_name: string, disk: string, path: string, mime_type: string, extension: ?string, size: int, sha256: string} */
    private function storeFile(CaseFile $caseFile, UploadedFile $file): array
    {
        $extension = $file->extension();
        $storedName = Str::uuid().($extension === '' ? '' : '.'.$extension);
        $path = Storage::disk('legal_private')->putFileAs('case-files/'.$caseFile->id, $file, $storedName);
        if (! is_string($path)) {
            throw new RuntimeException('Evrak güvenli depolama alanına yazılamadı.');
        }

        $sha256 = hash_file('sha256', $file->getRealPath());
        if (! is_string($sha256)) {
            Storage::disk('legal_private')->delete($path);
            throw new RuntimeException('Evrak bütünlük özeti üretilemedi.');
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => 'legal_private',
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'extension' => $extension ?: null,
            'size' => $file->getSize(),
            'sha256' => $sha256,
        ];
    }

    /** @param array{original_name: string, stored_name: string, disk: string, path: string, mime_type: string, extension: ?string, size: int, sha256: string} $metadata */
    private function createVersion(Document $document, array $metadata, User $actor, int $versionNo, ?string $changeNote): DocumentVersion
    {
        $version = new DocumentVersion(['change_note' => $changeNote]);
        $version->forceFill([
            ...$metadata,
            'document_id' => $document->id,
            'version_no' => $versionNo,
            'uploaded_by' => $actor->id,
        ])->save();

        return $version;
    }
}
