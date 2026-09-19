<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeadlineRequest;
use App\Http\Requests\UpdateDeadlineRequest;
use App\Models\CaseFile;
use App\Models\Deadline;
use App\Models\User;
use App\Services\DeadlineService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeadlineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Deadline::class);
        $deadlines = Deadline::query()->visibleTo($request->user())->with(['caseFile', 'assignedLawyer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->string('period')->toString() === 'overdue', fn ($query) => $query->where('status', 'open')->where('due_at', '<', now()))
            ->when($request->string('period')->toString() === 'week', fn ($query) => $query->whereBetween('due_at', [now(), now()->addWeek()]))
            ->orderBy('due_at')->paginate(25)->withQueryString();

        return view('deadlines.index', compact('deadlines'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Deadline::class);

        $selectedCase = $request->filled('case_file') ? CaseFile::query()->visibleTo($request->user())->find($request->integer('case_file')) : null;

        return view('deadlines.form', ['deadline' => new Deadline(['case_file_id' => $selectedCase?->id]), ...$this->formData($request->user(), $selectedCase)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeadlineRequest $request, DeadlineService $deadlines): RedirectResponse
    {
        $deadline = $deadlines->create($request->validated(), $request->user());

        return redirect()->route('deadlines.edit', $deadline)->with('success', 'Hukuki süre oluşturuldu.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Deadline $deadline): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Deadline $deadline): View
    {
        Gate::authorize('update', $deadline);

        return view('deadlines.form', ['deadline' => $deadline, ...$this->formData($request->user(), $deadline->caseFile)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeadlineRequest $request, Deadline $deadline, DeadlineService $deadlines): RedirectResponse
    {
        $deadlines->update($deadline, $request->validated(), $request->user());

        return redirect()->route('deadlines.index')->with('success', 'Hukuki süre güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deadline $deadline): never
    {
        abort(405);
    }

    /** @return array{caseFiles: Collection<int, CaseFile>, lawyers: Collection<int, User>} */
    private function formData(User $user, ?CaseFile $currentCase = null): array
    {
        return [
            'caseFiles' => CaseFile::query()->visibleTo($user)->where(fn ($query) => $query->where('status', '!=', 'closed')->when($currentCase, fn ($cases) => $cases->orWhereKey($currentCase)))->orderBy('case_no')->get(),
            'lawyers' => $currentCase?->activeLawyers()->orderBy('name')->get()
                ?? ($user->isManager() ? User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->orderBy('name')->get() : collect([$user])),
        ];
    }
}
