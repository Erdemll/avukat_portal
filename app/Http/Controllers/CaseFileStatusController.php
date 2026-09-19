<?php

namespace App\Http\Controllers;

use App\CaseFileStatus;
use App\Http\Requests\UpdateCaseFileStatusRequest;
use App\Models\CaseFile;
use App\Services\CaseFileManagementService;
use Illuminate\Http\RedirectResponse;

class CaseFileStatusController extends Controller
{
    public function __invoke(UpdateCaseFileStatusRequest $request, CaseFile $caseFile, CaseFileManagementService $caseFiles): RedirectResponse
    {
        $caseFiles->changeStatus(
            $caseFile,
            $request->enum('status', CaseFileStatus::class),
            $request->user(),
            $request->string('reason')->toString() ?: null,
            $request->integer('lock_version'),
        );

        return back()->with('success', 'Dosya durumu güncellendi.');
    }
}
