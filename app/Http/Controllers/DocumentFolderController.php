<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentFolderRequest;
use App\Models\CaseFile;
use App\Services\DocumentFolderService;
use Illuminate\Http\RedirectResponse;

class DocumentFolderController extends Controller
{
    public function store(StoreDocumentFolderRequest $request, CaseFile $caseFile, DocumentFolderService $folders): RedirectResponse
    {
        $folders->create($caseFile, $request->string('name')->toString(), $request->user());

        return back()->with('success', 'Evrak klasörü oluşturuldu.');
    }
}
