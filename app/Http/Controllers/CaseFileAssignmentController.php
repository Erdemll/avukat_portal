<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCaseFileAssignmentsRequest;
use App\Models\CaseFile;
use App\Services\CaseFileAssignmentService;
use Illuminate\Http\RedirectResponse;

class CaseFileAssignmentController extends Controller
{
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
