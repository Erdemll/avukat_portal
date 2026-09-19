<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLegalTaskRequest;
use App\Http\Requests\UpdateLegalTaskRequest;
use App\Models\CaseFile;
use App\Models\LegalTask;
use App\Models\User;
use App\Services\LegalTaskService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LegalTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LegalTask::class);
        $tasks = LegalTask::query()->visibleTo($request->user())->with(['caseFile', 'assignee'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->string('scope')->toString() === 'mine', fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')->paginate(25)->withQueryString();

        return view('legal-tasks.index', compact('tasks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', LegalTask::class);

        $selectedCase = $request->filled('case_file') ? CaseFile::query()->visibleTo($request->user())->find($request->integer('case_file')) : null;

        return view('legal-tasks.form', ['legalTask' => new LegalTask(['case_file_id' => $selectedCase?->id, 'assigned_to' => $request->user()->id]), ...$this->formData($request->user(), $selectedCase)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLegalTaskRequest $request, LegalTaskService $tasks): RedirectResponse
    {
        $task = $tasks->create($request->validated(), $request->user());

        return redirect()->route('legal-tasks.edit', $task)->with('success', 'Görev oluşturuldu.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LegalTask $legalTask): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, LegalTask $legalTask): View
    {
        Gate::authorize('update', $legalTask);

        return view('legal-tasks.form', ['legalTask' => $legalTask, ...$this->formData($request->user(), $legalTask->caseFile)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLegalTaskRequest $request, LegalTask $legalTask, LegalTaskService $tasks): RedirectResponse
    {
        $tasks->update($legalTask, $request->validated(), $request->user());

        return redirect()->route('legal-tasks.index')->with('success', 'Görev güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LegalTask $legalTask): never
    {
        abort(405);
    }

    /** @return array{caseFiles: Collection<int, CaseFile>, assignees: Collection<int, User>} */
    private function formData(User $user, ?CaseFile $currentCase = null): array
    {
        return [
            'caseFiles' => CaseFile::query()->visibleTo($user)->where(fn ($query) => $query->where('status', '!=', 'closed')->when($currentCase, fn ($cases) => $cases->orWhereKey($currentCase)))->orderBy('case_no')->get(),
            'assignees' => $user->isManager()
                ? User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->whereIn('slug', ['lawyer', 'manager']))->orderBy('name')->get()
                : ($currentCase?->activeLawyers()->orderBy('name')->get() ?? collect([$user])),
        ];
    }
}
