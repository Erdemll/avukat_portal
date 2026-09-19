<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHearingRequest;
use App\Http\Requests\UpdateHearingRequest;
use App\Models\CaseFile;
use App\Models\Hearing;
use App\Models\User;
use App\Services\HearingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HearingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Hearing::class);
        $hearings = Hearing::query()->visibleTo($request->user())->with(['caseFile', 'lawyer'])
            ->when($request->filled('period') && $request->string('period')->toString() === 'today', fn ($query) => $query->whereDate('hearing_at', today()))
            ->when($request->filled('period') && $request->string('period')->toString() === 'week', fn ($query) => $query->whereBetween('hearing_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy('hearing_at')->paginate(25)->withQueryString();

        return view('hearings.index', compact('hearings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Hearing::class);

        $selectedCase = $request->filled('case_file') ? CaseFile::query()->visibleTo($request->user())->find($request->integer('case_file')) : null;

        return view('hearings.form', ['hearing' => new Hearing(['case_file_id' => $selectedCase?->id]), ...$this->formData($request->user(), $selectedCase)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHearingRequest $request, HearingService $hearings): RedirectResponse
    {
        $hearing = $hearings->create($request->validated(), $request->user());

        return redirect()->route('hearings.edit', $hearing)->with('success', 'Duruşma oluşturuldu.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hearing $hearing): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Hearing $hearing): View
    {
        Gate::authorize('update', $hearing);

        return view('hearings.form', ['hearing' => $hearing, ...$this->formData($request->user(), $hearing->caseFile)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHearingRequest $request, Hearing $hearing, HearingService $hearings): RedirectResponse
    {
        $hearings->update($hearing, $request->validated(), $request->user());

        return redirect()->route('hearings.index')->with('success', 'Duruşma güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hearing $hearing): never
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
