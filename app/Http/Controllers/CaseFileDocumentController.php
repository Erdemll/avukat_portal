<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseFileDocumentRequest;
use App\Models\CaseFile;
use App\Services\CaseDocumentService;
use Illuminate\Http\RedirectResponse;

class CaseFileDocumentController extends Controller
{
    public function store(StoreCaseFileDocumentRequest $request, CaseFile $caseFile, CaseDocumentService $documents): RedirectResponse
    {
        $documents->storeMany($caseFile, $request->file('documents'), $request->safe()->except('documents'), $request->user());

        return back()->with('success', 'Evraklar hukuki dosyaya yüklendi.');
    }
}
