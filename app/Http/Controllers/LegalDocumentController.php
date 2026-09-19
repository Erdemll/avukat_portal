<?php

namespace App\Http\Controllers;

use App\Models\CaseFile;
use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);
        $user = $request->user();
        $search = str_replace(['%', '_'], ['\%', '\_'], $request->string('search')->trim()->toString());
        $documents = Document::query()
            ->visibleTo($user)
            ->whereNotNull('case_file_id')
            ->whereNull('archived_at')
            ->with(['caseFile', 'folder', 'currentVersion.uploader', 'versions.uploader'])
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('original_name', 'like', '%'.$search.'%')
                ->orWhereHas('caseFile', fn ($caseFiles) => $caseFiles->where(fn ($caseSearch) => $caseSearch
                    ->where('case_no', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')))))
            ->when($request->filled('document_type'), fn ($query) => $query->where('document_type', $request->string('document_type')->toString()))
            ->when($request->filled('case_file'), fn ($query) => $query->where('case_file_id', $request->integer('case_file')))
            ->when($request->filled('folder'), fn ($query) => $query->where('folder_id', $request->integer('folder')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'caseFiles' => CaseFile::query()->visibleTo($user)->orderByDesc('updated_at')->limit(100)->get(),
            'folders' => DocumentFolder::query()->whereHas('caseFile', fn ($cases) => $cases->visibleTo($user))->orderBy('name')->get(),
        ]);
    }
}
