<?php

namespace App\Http\Controllers\Admin;

use App\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventTypeRequest;
use App\Http\Requests\UpdateEventTypeRequest;
use App\Models\EventType;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventTypeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EventType::class);
        $status = $request->string('status')->toString();
        abort_unless(in_array($status, ['', 'all', 'active', 'inactive'], true), 422);
        $search = str_replace(['%', '_'], ['\%', '\_'], $request->string('search')->toString());
        $eventTypes = EventType::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($subquery) => $subquery->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%')))->when($status === 'active', fn ($q) => $q->where('is_active', true))->when($status === 'inactive', fn ($q) => $q->where('is_active', false))->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.event-types.index', compact('eventTypes'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', EventType::class);

        return view('admin.event-types.form', ['eventType' => new EventType]);
    }

    public function store(StoreEventTypeRequest $request, AuditService $audit): RedirectResponse
    {
        Gate::authorize('create', EventType::class);
        $slug = Str::slug($request->string('name'));
        if (EventType::query()->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Bu isimle oluşturulan slug zaten kullanılıyor.']);
        }
        DB::transaction(function () use ($request, $audit, $slug): void {
            $type = EventType::query()->create([...$request->validated(), 'slug' => $slug, 'is_active' => true]);
            $audit->log(AuditAction::EventTypeCreated, $request->user(), auditable: $type, description: 'Olay türü oluşturuldu.', newValues: $type->only(['name', 'description', 'is_active']));
        });

        return redirect()->route('admin.event-types.index');
    }

    public function edit(EventType $eventType): View
    {
        Gate::authorize('update', $eventType);

        return view('admin.event-types.form', compact('eventType'));
    }

    public function update(UpdateEventTypeRequest $request, EventType $eventType, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $eventType);
        DB::transaction(function () use ($request, $eventType, $audit): void {
            $old = $eventType->only(['name', 'description', 'is_active']);
            $eventType->fill($request->validated())->save();
            $audit->log(AuditAction::EventTypeUpdated, $request->user(), auditable: $eventType, description: 'Olay türü güncellendi.', oldValues: $old, newValues: $eventType->only(['name', 'description', 'is_active']));
        });

        return redirect()->route('admin.event-types.index');
    }

    public function activate(Request $request, EventType $eventType, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $eventType);
        DB::transaction(function () use ($request, $eventType, $audit): void {
            $eventType->update(['is_active' => true]);
            $audit->log(AuditAction::EventTypeActivated, $request->user(), auditable: $eventType, description: 'Olay türü aktifleştirildi.', newValues: ['is_active' => true]);
        });

        return back();
    }

    public function deactivate(Request $request, EventType $eventType, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $eventType);
        DB::transaction(function () use ($request, $eventType, $audit): void {
            $eventType->update(['is_active' => false]);
            $audit->log(AuditAction::EventTypeDeactivated, $request->user(), auditable: $eventType, description: 'Olay türü pasifleştirildi.', newValues: ['is_active' => false]);
        });

        return back();
    }
}
