<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertEventToCaseFileRequest;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Event;
use App\Models\User;
use App\Services\EventCaseFileConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventCaseFileController extends Controller
{
    public function create(Request $request, Event $event): View
    {
        Gate::authorize('view', $event);
        Gate::authorize('create', CaseFile::class);

        return view('case-files.form', [
            'caseFile' => new CaseFile([
                'title' => $event->title,
                'description' => $event->description,
                'priority' => $event->priority,
                'opened_at' => today(),
            ]),
            'sourceEvent' => $event,
            'caseTypes' => CaseType::query()->where('is_active', true)->orderBy('name')->get(),
            'lawyers' => $request->user()->isManager()
                ? User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->orderBy('name')->get()
                : collect([$request->user()]),
            'clients' => Client::query()->visibleTo($request->user())->with('party')->where('status', 'active')->latest()->limit(100)->get(),
        ]);
    }

    public function store(ConvertEventToCaseFileRequest $request, Event $event, EventCaseFileConversionService $conversion): RedirectResponse
    {
        $caseFile = $conversion->convert($event, $request->validated(), $request->user());

        return redirect()->route('case-files.show', $caseFile)->with('success', 'Hukuki talep dosyaya dönüştürüldü.');
    }
}
