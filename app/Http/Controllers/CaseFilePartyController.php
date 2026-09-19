<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseFilePartyRequest;
use App\Models\CaseFile;
use App\Models\CaseFileParty;
use App\Services\CaseFilePartyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CaseFilePartyController extends Controller
{
    public function store(StoreCaseFilePartyRequest $request, CaseFile $caseFile, CaseFilePartyService $parties): RedirectResponse
    {
        $parties->add($caseFile, $request->validated(), $request->user());

        return back()->with('success', 'Dosyaya taraf eklendi.');
    }

    public function destroy(CaseFile $caseFile, CaseFileParty $caseFileParty, CaseFilePartyService $parties): RedirectResponse
    {
        Gate::authorize('manageParties', $caseFile);
        abort_unless($caseFileParty->case_file_id === $caseFile->id, 404);
        $parties->remove($caseFile, $caseFileParty, request()->user());

        return back()->with('success', 'Taraf ilişkisi sonlandırıldı.');
    }
}
