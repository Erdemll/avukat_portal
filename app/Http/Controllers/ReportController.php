<?php

namespace App\Http\Controllers;

use App\CaseFileStatus;
use App\DeadlineStatus;
use App\HearingStatus;
use App\Models\CaseFile;
use App\Models\CaseFinancialEntry;
use App\Models\CaseType;
use App\Models\Deadline;
use App\Models\Hearing;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);
        [$from, $to] = $this->dates($request);

        return view('reports.index', $this->reportData($from, $to) + compact('from', 'to'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isManager(), 403);
        [$from, $to] = $this->dates($request);
        $data = $this->reportData($from, $to);

        return response()->streamDownload(function () use ($data, $from, $to): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Bölüm', 'Gösterge', 'Değer'], ';');
            fputcsv($stream, ['Dönem', 'Başlangıç', $from->format('d.m.Y')], ';');
            fputcsv($stream, ['Dönem', 'Bitiş', $to->format('d.m.Y')], ';');
            foreach ($data['caseStatusCounts'] as $status => $count) {
                fputcsv($stream, ['Dosya durumu', CaseFileStatus::from($status)->label(), $count], ';');
            }
            foreach ($data['caseTypeCounts'] as $row) {
                fputcsv($stream, ['Dosya türü', $this->csvCell($row->name), $row->case_files_count], ';');
            }
            foreach ($data['financialTotals'] as $row) {
                fputcsv($stream, ['Finans', $row->type->label().' ('.$row->currency.')', $row->total], ';');
            }
            fputcsv($stream, ['Operasyon', 'Gecikmiş açık süre', $data['overdueDeadlineCount']], ';');
            fputcsv($stream, ['Operasyon', 'Önümüzdeki 30 gündeki duruşma', $data['upcomingHearingCount']], ';');
            fclose($stream);
        }, 'hukuk-raporu-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** @return array{Carbon, Carbon} */
    private function dates(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfYear(),
            isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay(),
        ];
    }

    /** @return array<string, Collection|int> */
    private function reportData(Carbon $from, Carbon $to): array
    {
        $caseStatusCounts = CaseFile::query()
            ->whereBetween('opened_at', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $caseTypeCounts = CaseType::query()->withCount(['caseFiles' => fn ($query) => $query
            ->whereBetween('opened_at', [$from->toDateString(), $to->toDateString()])])
            ->orderByDesc('case_files_count')->get()
            ->filter(fn (CaseType $caseType): bool => $caseType->case_files_count > 0)
            ->values();

        $financialTotals = CaseFinancialEntry::query()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('type, currency, SUM(amount) as total')
            ->groupBy('type', 'currency')->orderBy('currency')->orderBy('type')->get();

        return [
            'caseStatusCounts' => $caseStatusCounts,
            'caseTypeCounts' => $caseTypeCounts,
            'financialTotals' => $financialTotals,
            'overdueDeadlineCount' => Deadline::query()->where('status', DeadlineStatus::Open)->where('due_at', '<', now())->count(),
            'upcomingHearingCount' => Hearing::query()->where('status', HearingStatus::Scheduled)->whereBetween('hearing_at', [now(), now()->addDays(30)])->count(),
        ];
    }

    private function csvCell(string $value): string
    {
        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
