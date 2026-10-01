<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCaseFileAssignmentsRequest;
use App\Models\CaseFile;
use App\Models\User;
use App\Services\CaseFileAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseFileAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isManager() || $request->user()->isAssistant(), 403);
        $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->trim()->toString());

        return view('case-files.assignment-index', [
            'caseFiles' => CaseFile::query()
                ->with(['caseType', 'activeLawyers'])
                ->when($search !== '', fn ($query) => $query->where(fn ($cases) => $cases
                    ->where('case_no', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')))
                ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(CaseFile $caseFile): View
    {
        Gate::authorize('assign', $caseFile);
        $caseFile->load(['caseType', 'activeLawyers', 'assignments.lawyer']);

        return view('case-files.assignment-only', [
            'caseFile' => $caseFile,
            'lawyers' => User::query()->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))
                ->orderBy('name')->get(),
        ]);
    }

    public function __invoke(UpdateCaseFileAssignmentsRequest $request, CaseFile $caseFile, CaseFileAssignmentService $assignments): RedirectResponse
    {
        $assignments->sync(
            $caseFile,
            $request->user(),
            $request->input('lawyer_ids'),
            $request->integer('lead_lawyer_id'),
            $request->string('reason')->toString() ?: null,
            $request->integer('lock_version'),
        );

        return back()->with('success', 'Dosya avukatları güncellendi.');
    }
}
