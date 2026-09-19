<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewCaseAssignmentRequest;
use App\Http\Requests\StoreCaseAssignmentRequest;
use App\Models\CaseAssignmentRequest;
use App\Models\CaseFile;
use App\Models\User;
use App\Services\CaseAssignmentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseAssignmentRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CaseAssignmentRequest::class);
        $requests = CaseAssignmentRequest::query()->visibleTo($request->user())->with(['caseFile', 'requester', 'requestedTo', 'reviewer'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()->paginate(25)->withQueryString();

        return view('assignment-requests.index', [
            'requests' => $requests,
            'transferCaseFiles' => $request->user()->isLawyer() ? CaseFile::query()->visibleTo($request->user())->where('status', '!=', 'closed')->orderBy('case_no')->get() : collect(),
            'lawyers' => User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCaseAssignmentRequest $request, CaseAssignmentRequestService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return back()->with('success', 'Dosya talebi oluşturuldu.');
    }

    public function review(ReviewCaseAssignmentRequest $request, CaseAssignmentRequest $caseAssignmentRequest, CaseAssignmentRequestService $service): RedirectResponse
    {
        $service->review($caseAssignmentRequest, $request->validated(), $request->user());

        return back()->with('success', 'Dosya talebi sonuçlandırıldı.');
    }

    public function cancel(CaseAssignmentRequest $caseAssignmentRequest, CaseAssignmentRequestService $service): RedirectResponse
    {
        Gate::authorize('cancel', $caseAssignmentRequest);
        $service->cancel($caseAssignmentRequest, request()->user());

        return back()->with('success', 'Dosya talebi iptal edildi.');
    }
}
