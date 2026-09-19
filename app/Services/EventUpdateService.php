<?php

namespace App\Services;

use App\AuditAction;
use App\Models\Event;
use App\Models\EventUpdate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventUpdateService
{
    public function __construct(private DocumentService $documents, private AuditService $audit) {}

    /** @param array<int, UploadedFile> $files */
    public function create(Event $event, User $user, string $description, ?string $title, ?string $savcilik = null, array $files = []): EventUpdate
    {
        $storedDocuments = collect();

        try {
            return DB::transaction(function () use ($event, $user, $description, $title, $savcilik, $files, &$storedDocuments): EventUpdate {
                $update = new EventUpdate(['title' => $title, 'savcilik' => $savcilik, 'description' => $description]);
                $update->event_id = $event->id;
                $update->user_id = $user->id;
                $update->save();
                $event->forceFill(['current_process' => $description])->save();

                foreach ($files as $file) {
                    $storedDocuments->push($this->documents->storeForEvent($event, $file, $user, $update));
                }

                $this->audit->log(AuditAction::EventUpdateCreated, $user, $event, $update, 'Süreç güncellemesi oluşturuldu.', [], ['title' => $update->title]);
                foreach ($storedDocuments as $document) {
                    $this->audit->log(AuditAction::DocumentUploaded, $user, $event, $document, 'Belge yüklendi.', [], ['original_name' => $document->original_name, 'mime_type' => $document->mime_type, 'size' => $document->size, 'event_update_id' => $update->id]);
                }

                return $update;
            });
        } catch (Throwable $exception) {
            $this->documents->deleteStoredFiles($storedDocuments);
            throw $exception;
        }
    }
}
