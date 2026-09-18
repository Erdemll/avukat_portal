<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\StoreEventUpdateRequest;
use App\Models\Event;
use App\Services\AuditService;
use App\Services\EventNotificationService;
use App\Services\EventUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EventUpdateController extends Controller
{
    public function store(StoreEventUpdateRequest $request, Event $event, EventUpdateService $updates, AuditService $audit, EventNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('createUpdate', $event);
        $update = $updates->create(
            $event,
            $request->user(),
            $request->string('description')->toString(),
            $request->string('title')->toString() ?: null,
            $request->string('savcilik')->toString() ?: null,
            $request->file('documents', [])
        );
        $audit->safelyLog(AuditAction::EventUpdateCreated, $request->user(), $event, $update, 'Süreç güncellemesi oluşturuldu.', [], ['title' => $update->title]);
        $notifications->updated($update->load('event.creator'), $request->user());
        foreach ($update->documents as $document) {
            $audit->safelyLog(AuditAction::DocumentUploaded, $request->user(), $event, $document, 'Belge yüklendi.', [], ['original_name' => $document->original_name, 'mime_type' => $document->mime_type, 'size' => $document->size, 'event_update_id' => $update->id]);
            $notifications->documentUploaded($document->load('event.creator', 'event.assignedLawyer'), $request->user());
        }

        return redirect()->route('events.show', $event);
    }
}
