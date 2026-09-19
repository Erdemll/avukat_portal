<x-layouts.app title="Yönetim Raporları">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Yönetim görünümü</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Hukuk operasyon raporu</h1>
            <p class="mt-2 text-sm text-slate-600">Dosya açılışları ve finans hareketleri seçilen döneme, operasyon sayaçları bugüne göre hesaplanır.</p>
        </div>
        <a class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-center text-sm font-semibold text-indigo-700 hover:bg-indigo-100" href="{{ route('reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">CSV dışa aktar</a>
    </div>

    <form class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" method="GET">
        <div><label class="block text-sm font-medium text-slate-700" for="from">Başlangıç</label><input class="mt-1 rounded-lg border-slate-300" id="from" name="from" type="date" value="{{ $from->toDateString() }}"></div>
        <div><label class="block text-sm font-medium text-slate-700" for="to">Bitiş</label><input class="mt-1 rounded-lg border-slate-300" id="to" name="to" type="date" value="{{ $to->toDateString() }}"></div>
        <button class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-800">Raporu güncelle</button>
    </form>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(\App\CaseFileStatus::cases() as $status)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ $status->label() }} dosya</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $caseStatusCounts[$status->value] ?? 0 }}</p></div>
        @endforeach
        <div class="rounded-xl border border-red-200 bg-red-50 p-5"><p class="text-sm text-red-700">Gecikmiş açık süre</p><p class="mt-2 text-3xl font-bold text-red-900">{{ $overdueDeadlineCount }}</p></div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5"><p class="text-sm text-blue-700">30 günlük duruşma</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $upcomingHearingCount }}</p></div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Dosya türleri</h2>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><tbody class="divide-y divide-slate-100">@forelse($caseTypeCounts as $row)<tr><td class="px-5 py-3 text-slate-700">{{ $row->name }}</td><td class="px-5 py-3 text-right font-semibold text-slate-900">{{ $row->case_files_count }}</td></tr>@empty<tr><td class="px-5 py-8 text-center text-slate-500">Bu dönemde açılan dosya yok.</td></tr>@endforelse</tbody></table></div>
        </section>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Finans toplamları</h2>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><tbody class="divide-y divide-slate-100">@forelse($financialTotals as $row)<tr><td class="px-5 py-3 text-slate-700">{{ $row->type->label() }}</td><td class="px-5 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $row->total, 2, ',', '.') }} {{ $row->currency }}</td></tr>@empty<tr><td class="px-5 py-8 text-center text-slate-500">Bu dönemde finans hareketi yok.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
</x-layouts.app>
