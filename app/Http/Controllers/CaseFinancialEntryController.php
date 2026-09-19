<?php

namespace App\Http\Controllers;

use App\FinancialEntryType;
use App\Http\Requests\ReverseCaseFinancialEntryRequest;
use App\Http\Requests\StoreCaseFinancialEntryRequest;
use App\Models\CaseFile;
use App\Models\CaseFinancialEntry;
use App\Services\CaseFinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseFinancialEntryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CaseFinancialEntry::class);
        $user = $request->user();
        $base = CaseFinancialEntry::query()->visibleTo($user);
        $entries = (clone $base)->with(['caseFile', 'creator', 'reversal', 'reversalOf'])
            ->when($request->filled('case_file'), fn ($query) => $query->where('case_file_id', $request->integer('case_file')))
            ->latest('transaction_date')->latest('id')->paginate(30)->withQueryString();
        $summaries = (clone $base)->whereNull('reversal_of_id')->whereDoesntHave('reversal')
            ->when($request->filled('case_file'), fn ($query) => $query->where('case_file_id', $request->integer('case_file')))
            ->selectRaw('currency, SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as receivable, SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as payment, SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense', [FinancialEntryType::Receivable->value, FinancialEntryType::Payment->value, FinancialEntryType::Expense->value])
            ->groupBy('currency')->get()->keyBy('currency')->map(fn ($row) => ['receivable' => $row->receivable, 'payment' => $row->payment, 'expense' => $row->expense]);

        return view('financial-entries.index', [
            'entries' => $entries,
            'summaries' => $summaries,
            'caseFiles' => CaseFile::query()->visibleTo($user)->orderBy('case_no')->get(),
        ]);
    }

    public function store(StoreCaseFinancialEntryRequest $request, CaseFinancialService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return back()->with('success', 'Finans hareketi oluşturuldu.');
    }

    public function reverse(ReverseCaseFinancialEntryRequest $request, CaseFinancialEntry $caseFinancialEntry, CaseFinancialService $service): RedirectResponse
    {
        $service->reverse($caseFinancialEntry, $request->validated(), $request->user());

        return back()->with('success', 'Finans hareketi ters kaydedildi.');
    }
}
