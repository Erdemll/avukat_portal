<?php

namespace App\Http\Controllers\Admin;

use App\AuditAction;
use App\CaseTypeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseTypeRequest;
use App\Http\Requests\UpdateCaseTypeRequest;
use App\Models\CaseType;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseTypeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CaseType::class);
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();
        abort_unless(in_array($status, ['', 'all', 'active', 'inactive'], true), 422);
        abort_unless($category === '' || CaseTypeCategory::tryFrom($category) !== null, 422);
        $search = str_replace(['%', '_'], ['\%', '\_'], $request->string('search')->trim()->toString());
        $caseTypes = CaseType::query()
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%')))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.case-types.index', compact('caseTypes'));
    }

    public function create(): View
    {
        Gate::authorize('create', CaseType::class);

        return view('admin.case-types.form', ['caseType' => new CaseType]);
    }

    public function store(StoreCaseTypeRequest $request, AuditService $audit): RedirectResponse
    {
        $caseType = DB::transaction(function () use ($request, $audit): CaseType {
            $caseType = CaseType::query()->create([
                ...$request->validated(),
                'is_active' => true,
            ]);
            $audit->log(AuditAction::CaseTypeCreated, $request->user(), auditable: $caseType, description: 'Hukuki dosya türü oluşturuldu.', newValues: $caseType->only(['name', 'slug', 'category', 'is_active']));

            return $caseType;
        });

        return redirect()->route('admin.case-types.edit', $caseType)->with('success', 'Dosya türü oluşturuldu.');
    }

    public function edit(CaseType $caseType): View
    {
        Gate::authorize('update', $caseType);

        return view('admin.case-types.form', compact('caseType'));
    }

    public function update(UpdateCaseTypeRequest $request, CaseType $caseType, AuditService $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $caseType, $audit): void {
            $oldValues = $caseType->only(['name', 'category', 'description']);
            $caseType->update($request->validated());
            $audit->log(AuditAction::CaseTypeUpdated, $request->user(), auditable: $caseType, description: 'Hukuki dosya türü güncellendi.', oldValues: $oldValues, newValues: $caseType->only(['name', 'category', 'description']));
        });

        return redirect()->route('admin.case-types.index')->with('success', 'Dosya türü güncellendi.');
    }

    public function activate(Request $request, CaseType $caseType, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $caseType);
        DB::transaction(function () use ($request, $caseType, $audit): void {
            $caseType->update(['is_active' => true]);
            $audit->log(AuditAction::CaseTypeActivated, $request->user(), auditable: $caseType, description: 'Hukuki dosya türü aktifleştirildi.', newValues: ['is_active' => true]);
        });

        return back()->with('success', 'Dosya türü aktifleştirildi.');
    }

    public function deactivate(Request $request, CaseType $caseType, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $caseType);
        DB::transaction(function () use ($request, $caseType, $audit): void {
            $caseType->update(['is_active' => false]);
            $audit->log(AuditAction::CaseTypeDeactivated, $request->user(), auditable: $caseType, description: 'Hukuki dosya türü pasifleştirildi.', newValues: ['is_active' => false]);
        });

        return back()->with('success', 'Dosya türü pasifleştirildi.');
    }
}
