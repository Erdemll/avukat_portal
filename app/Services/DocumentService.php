<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Event;
use App\Models\EventUpdate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DocumentService
{
    public function storeForEvent(Event $event, UploadedFile $file, User $user, ?EventUpdate $eventUpdate = null): Document
    {
        $extension = $file->extension();
        $sha256 = hash_file('sha256', $file->getRealPath());
        if (! is_string($sha256)) {
            throw new RuntimeException('Belge bütünlük özeti üretilemedi.');
        }
        $storedName = Str::uuid().($extension === '' ? '' : '.'.$extension);
        $path = Storage::disk('legal_private')->putFileAs('events/'.$event->id, $file, $storedName);

        try {
            $document = new Document([
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'disk' => 'legal_private',
                'path' => $path,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'extension' => $extension ?: null,
                'size' => $file->getSize(),
            ]);
            $document->event_id = $event->id;
            $document->event_update_id = $eventUpdate?->id;
            $document->uploaded_by = $user->id;
            $document->save();

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
                'uploaded_by' => $user->id,
                'change_note' => 'İlk sürüm',
            ])->save();

            return $document;
        } catch (Throwable $exception) {
            Storage::disk('legal_private')->delete($path);
            throw $exception;
        }
    }

    public function deleteStoredFiles(iterable $documents): void
    {
        foreach ($documents as $document) {
            Storage::disk($document->disk)->delete($document->path);
        }
    }
}
