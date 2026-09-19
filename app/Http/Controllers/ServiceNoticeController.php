<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceNoticeRequest;
use App\Http\Requests\UpdateServiceNoticeRequest;
use App\Models\CaseFile;
use App\Models\Document;
use App\Models\ServiceNotice;
use App\Services\ServiceNoticeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceNoticeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ServiceNotice::class);
        $notices = ServiceNotice::query()->visibleTo($request->user())->with(['caseFile', 'document'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('case_file'), fn ($query) => $query->where('case_file_id', $request->integer('case_file')))
            ->orderByDesc('service_date')->paginate(25)->withQueryString();

        return view('service-notices.index', ['notices' => $notices, 'caseFiles' => $this->caseFiles($request)]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', ServiceNotice::class);

        return view('service-notices.form', ['serviceNotice' => new ServiceNotice(['case_file_id' => $request->integer('case_file') ?: null]), 'caseFiles' => $this->caseFiles($request), 'documents' => Document::query()->visibleTo($request->user())->whereNotNull('case_file_id')->latest()->limit(200)->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceNoticeRequest $request, ServiceNoticeService $service): RedirectResponse
    {
        $notice = $service->create($request->validated(), $request->user());

        return redirect()->route('service-notices.edit', $notice)->with('success', 'Tebligat kaydedildi.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceNotice $serviceNotice): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ServiceNotice $serviceNotice): View
    {
        Gate::authorize('update', $serviceNotice);

        return view('service-notices.form', ['serviceNotice' => $serviceNotice, 'caseFiles' => $this->caseFiles($request), 'documents' => Document::query()->visibleTo($request->user())->where('case_file_id', $serviceNotice->case_file_id)->latest()->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceNoticeRequest $request, ServiceNotice $serviceNotice, ServiceNoticeService $service): RedirectResponse
    {
        $service->update($serviceNotice, $request->validated(), $request->user());

        return redirect()->route('service-notices.index')->with('success', 'Tebligat güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceNotice $serviceNotice): never
    {
        abort(405);
    }

    /** @return Collection<int, CaseFile> */
    private function caseFiles(Request $request): Collection
    {
        return CaseFile::query()->visibleTo($request->user())->orderBy('case_no')->get();
    }
}
