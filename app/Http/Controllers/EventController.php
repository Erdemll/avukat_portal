<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\EventPriority;
use App\EventStatus;
use App\Http\Requests\ListEventsRequest;
use App\Http\Requests\ReassignEventLawyerRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventPriorityRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Requests\UpdateEventStatusRequest;
use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EventManagementService;
use App\Services\EventNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(ListEventsRequest $request): View
    {
        Gate::authorize('viewAny', Event::class);
        $user = $request->user();
        $filters = $request->validated();
        $events = Event::query()
            ->visibleTo($user)
            ->with(['eventType', 'creator', 'assignedLawyer'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = str_replace(['%', '_'], ['\%', '\_'], $request->string('search')->trim()->toString());
                $query->where(function ($query) use ($search): void {
                    $query->where('event_no', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhereHas('creator', fn ($creator) => $creator->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('assignedLawyer', fn ($lawyer) => $lawyer->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($request->filled('event_type'), fn ($query) => $query->where('event_type_id', $request->integer('event_type')))
            ->when($request->filled('status'), fn ($query) => $query->where('system_status', $request->string('status')->toString()))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('assigned_lawyer'), fn ($query) => $query->where('assigned_lawyer_id', $request->integer('assigned_lawyer')))
            ->when($request->filled('creator'), fn ($query) => $query->where('created_by', $request->integer('creator')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderBy(['event_no' => 'event_no', 'created_at' => 'created_at', 'updated_at' => 'updated_at', 'priority' => 'priority'][$filters['sort'] ?? 'updated_at'], $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $visibleEvents = Event::query()->visibleTo($user);

        return view('events.index', [
            'events' => $events,
            'eventTypes' => EventType::query()->where('is_active', true)->orderBy('name')->get(),
            'lawyers' => User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->orderBy('name')->get(),
            'creators' => User::query()->whereIn('id', $visibleEvents->select('created_by'))->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Event::class);

        return view('events.form', ['event' => new Event, 'eventTypes' => EventType::query()->where('is_active', true)->get(), 'lawyers' => User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->get()]);
    }

    public function store(StoreEventRequest $request, AuditService $audit, EventNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('create', Event::class);
        $event = DB::transaction(function () use ($request, $audit, $notifications): Event {
            $event = new Event($request->validated());
            $event->created_by = $request->user()->id;
            $event->assigned_at = now();
            $event->save();
            $audit->log(AuditAction::EventCreated, $request->user(), $event, $event, 'Olay oluşturuldu.', [], ['event_no' => $event->event_no, 'event_type_id' => $event->event_type_id, 'assigned_lawyer_id' => $event->assigned_lawyer_id, 'title' => $event->title, 'priority' => $event->priority->value, 'system_status' => $event->system_status->value]);
            DB::afterCommit(fn () => $notifications->assigned($event->load('assignedLawyer'), $request->user()));

            return $event;
        });

        return redirect()->route('events.show', $event);
    }

    public function show(Event $event, AuditService $audit): View
    {
        Gate::authorize('view', $event);
        $event->load([
            'creator', 'assignedLawyer', 'eventType', 'documents.uploader', 'caseFiles',
            'updates' => fn ($query) => $query->latest(),
            'updates.user', 'updates.documents.uploader',
        ]);
        // Event page refreshes can grow this access log quickly; retention is a later concern.
        $audit->safelyLog(AuditAction::EventViewed, auth()->user(), $event, $event, 'Olay görüntülendi.');

        return view('events.show', [
            'event' => $event,
            'lawyers' => auth()->user()->isManager()
                ? User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        return view('events.form', ['event' => $event, 'eventTypes' => collect(), 'lawyers' => collect()]);
    }

    public function update(UpdateEventRequest $request, Event $event, AuditService $audit, EventNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('update', $event);
        DB::transaction(function () use ($request, $event, $audit, $notifications): void {
            $oldStatus = $event->system_status;
            $event->fill($request->validated());
            if ($event->isDirty('system_status')) {
                $event->closed_at = $event->system_status === EventStatus::Closed ? now() : null;
            }
            $event->save();
            if ($event->wasChanged('system_status')) {
                $action = $event->system_status->value === 'closed' ? AuditAction::EventClosed : AuditAction::EventStatusChanged;
                $audit->log($action, $request->user(), $event, $event, 'Olay durumu değiştirildi.', ['system_status' => $oldStatus->value], ['system_status' => $event->system_status->value]);
                if ($event->system_status->value === 'closed') {
                    DB::afterCommit(fn () => $notifications->closed($event->load(['creator', 'assignedLawyer']), $request->user()));
                }
            } else {
                $audit->log(AuditAction::EventUpdated, $request->user(), $event, $event, 'Olay güncellendi.');
            }
        });

        return redirect()->route('events.show', $event);
    }

    public function destroy(Event $event, AuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $event);
        DB::transaction(function () use ($event, $audit): void {
            $audit->log(AuditAction::EventDeleted, auth()->user(), $event, $event, 'Olay silindi.', [], ['event_no' => $event->event_no, 'title' => $event->title]);
            $event->delete();
        });

        return redirect()->route('events.index');
    }

    public function updateStatus(UpdateEventStatusRequest $request, Event $event, EventManagementService $management): RedirectResponse
    {
        Gate::authorize('update', $event);
        $management->changeStatus($event, $request->enum('status', EventStatus::class), $request->user());

        return redirect()->route('events.show', $event)->with('success', 'Olay durumu güncellendi.');
    }

    public function updatePriority(UpdateEventPriorityRequest $request, Event $event, EventManagementService $management): RedirectResponse
    {
        Gate::authorize('update', $event);
        $management->changePriority($event, $request->enum('priority', EventPriority::class), $request->user());

        return redirect()->route('events.show', $event)->with('success', 'Öncelik güncellendi.');
    }

    public function reassignLawyer(ReassignEventLawyerRequest $request, Event $event, EventManagementService $management): RedirectResponse
    {
        abort_unless($request->user()->isManager(), 403);
        $management->reassign($event, User::query()->findOrFail($request->integer('assigned_lawyer_id')), $request->user());

        return redirect()->route('events.show', $event)->with('success', 'Olay yeni avukata başarıyla atandı.');
    }
}
