<x-layouts.app title="Avukat Atamaları">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Hukuk Operasyonu</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Avukat Atamaları</h1>
            <p class="mt-1 text-sm text-slate-500">Dosya avukatlarını yönetin. Dosya içeriğine erişim kendi dosyalarınızla sınırlıdır.</p>
        </div>
        <a class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="{{ route('case-files.index') }}">Dosyalarım</a>
    </div>

    <form class="mt-6 flex gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" method="GET" action="{{ route('case-assignments.index') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Dosya numarası veya başlık" class="min-w-0 flex-1 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Ara</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse($caseFiles as $caseFile)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div class="min-w-0">
                        <p class="font-mono text-xs font-semibold text-indigo-600">{{ $caseFile->case_no }}</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $caseFile->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $caseFile->caseType->name }} · {{ $caseFile->activeLawyers->pluck('name')->join(', ') }}</p>
                    </div>
                    <a class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50" href="{{ route('case-files.assignments.show', $caseFile) }}">Atamaları Düzenle</a>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Dosya bulunamadı.</p>
            @endforelse
        </div>
    </div>

    @if($caseFiles->hasPages())<div class="mt-6">{{ $caseFiles->links() }}</div>@endif
</x-layouts.app>
