<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCaseFileAssistantsRequest;
use App\Models\CaseFile;
use App\Services\CaseFileAssistantService;
use Illuminate\Http\RedirectResponse;

class CaseFileAssistantController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateCaseFileAssistantsRequest $request, CaseFile $caseFile, CaseFileAssistantService $assistants): RedirectResponse
    {
        $assistants->sync($caseFile, $request->validated('assistant_ids', []), $request->integer('lock_version'), $request->user());

        return back()->with('success', 'Dosya asistanları güncellendi.');
    }
}
