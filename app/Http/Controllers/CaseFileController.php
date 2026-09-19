<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\CaseFileStatus;
use App\CaseTypeCategory;
use App\Http\Requests\StoreCaseFileRequest;
use App\Http\Requests\UpdateCaseFileRequest;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Party;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CaseFileManagementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CaseFile::class);
        $user = $request->user();
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();
        abort_unless($status === '' || CaseFileStatus::tryFrom($status) !== null, 422);
        abort_unless($category === '' || CaseTypeCategory::tryFrom($category) !== null, 422);
        $search = str_replace(['%', '_'], ['\%', '\_'], $request->string('search')->trim()->toString());

        $caseFiles = CaseFile::query()
            ->visibleTo($user)
            ->with(['caseType', 'activeLawyers'])
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('case_no', 'like', '%'.$search.'%')
                ->orWhere('title', 'like', '%'.$search.'%')
                ->orWhereHas('activeParties', fn ($parties) => $parties->where(fn ($party) => $party
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('surname', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%')))))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($category !== '', fn ($query) => $query->whereHas('caseType', fn ($types) => $types->where('category', $category)))
            ->when($request->filled('case_type'), fn ($query) => $query->where('case_type_id', $request->integer('case_type')))
            ->when($user->isManager() && $request->filled('lawyer'), fn ($query) => $query->whereHas('assignments', fn ($assignments) => $assignments
                ->where('lawyer_id', $request->integer('lawyer'))
                ->whereNull('ended_at')))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('case-files.index', [
            'caseFiles' => $caseFiles,
            'caseTypes' => CaseType::query()->where('is_active', true)->orderBy('name')->get(),
            'lawyers' => $user->isManager() ? $this->lawyers() : collect(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', CaseFile::class);

        return view('case-files.form', $this->formData($request->user(), new CaseFile));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCaseFileRequest $request, CaseFileManagementService $caseFiles): RedirectResponse
    {
        $caseFile = $caseFiles->create($request->validated(), $request->user());

        return redirect()->route('case-files.show', $caseFile)->with('success', 'Hukuki dosya oluşturuldu.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, CaseFile $caseFile, AuditService $audit): View
    {
        Gate::authorize('view', $caseFile);
        $caseFile->load([
            'caseType', 'creator', 'activeLawyers', 'assignments.lawyer',
            'events' => fn ($query) => $query->visibleTo($request->user()),
            'activeParties.client', 'proceedings',
            'documentFolders',
            'documents' => fn ($query) => $query->whereNull('archived_at')->with(['folder', 'currentVersion.uploader'])->latest()->limit(20),
            'hearings' => fn ($query) => $query->with('lawyer')->orderBy('hearing_at')->limit(10),
            'deadlines' => fn ($query) => $query->with('assignedLawyer')->orderBy('due_at')->limit(10),
            'tasks' => fn ($query) => $query->with('assignee')->orderBy('due_at')->limit(10),
            'serviceNotices' => fn ($query) => $query->with('document')->latest('service_date')->limit(10),
            'mediations' => fn ($query) => $query->latest('meeting_date')->limit(10),
            'financialEntries' => fn ($query) => $query->with(['reversal', 'reversalOf'])->latest('transaction_date')->limit(15),
            'clientCommunications' => fn ($query) => $query->with(['client.party', 'user'])->latest('communication_at')->limit(10),
            'assignmentRequests' => fn ($query) => $query->with(['requester', 'requestedTo'])->latest()->limit(10),
            'statusHistories' => fn ($query) => $query->with('changedBy')->latest('changed_at'),
            'notes' => fn ($query) => $query->with('author')->latest('occurred_at'),
        ]);
        $audit->safelyLog(AuditAction::CaseFileViewed, $request->user(), auditable: $caseFile, description: 'Hukuki dosya görüntülendi.', caseFile: $caseFile);
        $canUpdateCase = $request->user()->can('update', $caseFile);

        return view('case-files.show', [
            'caseFile' => $caseFile,
            'lawyers' => $request->user()->isManager() ? $this->lawyers() : collect(),
            'availableParties' => Party::query()->visibleTo($request->user())->orderBy('company_name')->orderBy('name')->limit(100)->get(),
            'canUpdateCase' => $canUpdateCase,
            'canManageParties' => $canUpdateCase,
            'canManageDocuments' => $canUpdateCase,
            'canManageOperations' => $canUpdateCase,
            'canAssignCase' => $request->user()->isManager(),
            'canCreateFinancialEntry' => $request->user()->isManager(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, CaseFile $caseFile): View
    {
        Gate::authorize('update', $caseFile);
        $caseFile->load('proceedings');

        return view('case-files.form', $this->formData($request->user(), $caseFile));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCaseFileRequest $request, CaseFile $caseFile, CaseFileManagementService $caseFiles): RedirectResponse
    {
        $caseFile = $caseFiles->update($caseFile, $request->validated(), $request->user());

        return redirect()->route('case-files.show', $caseFile)->with('success', 'Hukuki dosya güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CaseFile $caseFile): never
    {
        abort(405);
    }

    /** @return array<string, mixed> */
    private function formData(User $user, CaseFile $caseFile): array
    {
        return [
            'caseFile' => $caseFile,
            'sourceEvent' => null,
            'caseTypes' => CaseType::query()
                ->where(fn ($query) => $query->where('is_active', true)->when($caseFile->exists, fn ($types) => $types->orWhereKey($caseFile->case_type_id)))
                ->orderBy('name')
                ->get(),
            'lawyers' => $user->isManager() ? $this->lawyers() : collect([$user]),
            'clients' => Client::query()->visibleTo($user)->with('party')->where('status', 'active')->latest()->limit(100)->get(),
        ];
    }

    /** @return Collection<int, User> */
    private function lawyers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))
            ->orderBy('name')
            ->get();
    }
}
