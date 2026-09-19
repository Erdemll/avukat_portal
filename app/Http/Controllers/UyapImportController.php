<?php

namespace App\Http\Controllers;

use App\Http\Requests\UyapImportRequest;
use App\Services\UyapImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UyapImportController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);

        return view('uyap-import.create', ['preview' => null]);
    }

    public function store(UyapImportRequest $request, UyapImportService $importer): View|RedirectResponse
    {
        $preview = $importer->inspect($request->file('file'));
        if ($request->string('mode')->toString() === 'preview' || $preview['errors'] !== []) {
            return view('uyap-import.create', compact('preview'));
        }

        $count = $importer->import($preview['rows'], $request->user());

        return redirect()->route('uyap-import.create')->with('success', "{$count} UYAP kaydı hukuki dosyaya aktarıldı.");
    }
}
