<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventUpdateRequest;
use App\Models\Event;
use App\Services\EventNotificationService;
use App\Services\EventUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EventUpdateController extends Controller
{
    public function store(StoreEventUpdateRequest $request, Event $event, EventUpdateService $updates, EventNotificationService $notifications): RedirectResponse
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
        $notifications->updated($update->load('event.creator'), $request->user());
        foreach ($update->documents as $document) {
            $notifications->documentUploaded($document->load('event.creator', 'event.assignedLawyer'), $request->user());
        }

        return redirect()->route('events.show', $event);
    }
}
