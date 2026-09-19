<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('documents:backfill-versions {--chunk=200}')]
#[Description('Create immutable v1 records for legacy event documents')]
class BackfillDocumentVersions extends Command
{
    public function handle(): int
    {
        $lock = Cache::lock('documents:backfill-versions', 3600);
        if (! $lock->get()) {
            $this->warn('Başka bir belge sürümü aktarımı halen çalışıyor.');

            return self::FAILURE;
        }

        try {
            return $this->backfill();
        } finally {
            $lock->release();
        }
    }

    private function backfill(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $created = 0;
        $missing = 0;

        Document::query()->whereDoesntHave('versions')->chunkById($chunkSize, function ($documents) use (&$created, &$missing): void {
            foreach ($documents as $document) {
                if (! Storage::disk($document->disk)->exists($document->path)) {
                    $missing++;
                    $this->warn("Eksik dosya atlandı: document #{$document->id}");

                    continue;
                }

                $stream = Storage::disk($document->disk)->readStream($document->path);
                if (! is_resource($stream)) {
                    $missing++;
                    $this->warn("Okunamayan dosya atlandı: document #{$document->id}");

                    continue;
                }
                $hash = hash_init('sha256');
                hash_update_stream($hash, $stream);
                fclose($stream);
                $sha256 = hash_final($hash);

                $wasCreated = DB::transaction(function () use ($document, $sha256): bool {
                    $document = Document::query()->lockForUpdate()->findOrFail($document->id);
                    if ($document->versions()->exists()) {
                        return false;
                    }

                    $version = new DocumentVersion;
                    $version->forceFill([
                        'document_id' => $document->id,
                        'version_no' => 1,
                        'original_name' => $document->original_name,
                        'stored_name' => $document->stored_name,
                        'disk' => $document->disk,
                        'path' => $document->path,
                        'mime_type' => $document->mime_type,
                        'extension' => $document->extension,
                        'size' => $document->size,
                        'sha256' => $sha256,
                        'uploaded_by' => $document->uploaded_by,
                        'change_note' => 'Legacy belge v1 aktarımı',
                        'created_at' => $document->created_at,
                        'updated_at' => $document->created_at,
                    ])->save();

                    return true;
                });
                if ($wasCreated) {
                    $created++;
                }
            }
        });

        $this->info("{$created} belge sürümü oluşturuldu; {$missing} eksik dosya atlandı.");

        return self::SUCCESS;
    }
}
