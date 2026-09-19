<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediationRequest;
use App\Http\Requests\UpdateMediationRequest;
use App\Models\CaseFile;
use App\Models\Mediation;
use App\Services\MediationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MediationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Mediation::class);
        $mediations = Mediation::query()->visibleTo($request->user())->with('caseFile')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByDesc('meeting_date')->paginate(25)->withQueryString();

        return view('mediations.index', compact('mediations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Mediation::class);

        return view('mediations.form', ['mediation' => new Mediation(['case_file_id' => $request->integer('case_file') ?: null]), 'caseFiles' => $this->caseFiles($request)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediationRequest $request, MediationService $service): RedirectResponse
    {
        $mediation = $service->create($request->validated(), $request->user());

        return redirect()->route('mediations.edit', $mediation)->with('success', 'Arabuluculuk kaydedildi.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mediation $mediation): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Mediation $mediation): View
    {
        Gate::authorize('update', $mediation);

        return view('mediations.form', ['mediation' => $mediation, 'caseFiles' => $this->caseFiles($request)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediationRequest $request, Mediation $mediation, MediationService $service): RedirectResponse
    {
        $service->update($mediation, $request->validated(), $request->user());

        return redirect()->route('mediations.index')->with('success', 'Arabuluculuk güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mediation $mediation): never
    {
        abort(405);
    }

    /** @return Collection<int, CaseFile> */
    private function caseFiles(Request $request): Collection
    {
        return CaseFile::query()->visibleTo($request->user())->orderBy('case_no')->get();
    }
}
