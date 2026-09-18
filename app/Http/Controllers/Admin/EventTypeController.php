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
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);
        $status = $request->string('status')->toString();
        abort_unless(in_array($status, ['', 'all', 'active', 'inactive'], true), 422);
        $eventTypes = EventType::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($subquery) => $subquery->where('name', 'like', '%'.$request->string('search').'%')->orWhere('slug', 'like', '%'.$request->string('search').'%')))->when($status === 'active', fn ($q) => $q->where('is_active', true))->when($status === 'inactive', fn ($q) => $q->where('is_active', false))->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.event-types.index', compact('eventTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);

        return view('admin.event-types.form', ['eventType' => new EventType]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventTypeRequest $request, AuditService $audit): RedirectResponse
    {
        $slug = Str::slug($request->string('name'));
        if (EventType::query()->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Bu isimle oluşturulan slug zaten kullanılıyor.']);
        }
        $type = EventType::query()->create([...$request->validated(), 'slug' => $slug, 'is_active' => true]);
        $audit->safelyLog(AuditAction::EventTypeCreated, $request->user(), description: 'Olay türü oluşturuldu.', newValues: $type->only(['name', 'description', 'is_active']));

        return redirect()->route('admin.event-types.index');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Request $request, EventType $eventType): View
    {
        abort_unless($request->user()->isManager(), 403);

        return view('admin.event-types.form', compact('eventType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventTypeRequest $request, EventType $eventType, AuditService $audit): RedirectResponse
    {
        $old = $eventType->only(['name', 'description', 'is_active']);
        $eventType->fill($request->validated())->save();
        $audit->safelyLog(AuditAction::EventTypeUpdated, $request->user(), description: 'Olay türü güncellendi.', oldValues: $old, newValues: $eventType->only(['name', 'description', 'is_active']));

        return redirect()->route('admin.event-types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function activate(Request $request, EventType $eventType, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isManager(), 403);
        $eventType->update(['is_active' => true]);
        $audit->safelyLog(AuditAction::EventTypeActivated, $request->user(), description: 'Olay türü aktifleştirildi.', newValues: ['is_active' => true]);

        return back();
    }

    public function deactivate(Request $request, EventType $eventType, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isManager(), 403);
        $eventType->update(['is_active' => false]);
        $audit->safelyLog(AuditAction::EventTypeDeactivated, $request->user(), description: 'Olay türü pasifleştirildi.', newValues: ['is_active' => false]);

        return back();
    }
}
