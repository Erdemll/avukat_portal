<x-layouts.app title="Dosyalarım">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Hukuk Operasyonu</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Dosyalarım</h1>
            <p class="mt-1 text-sm text-slate-500">Aktif dava, icra ve diğer hukuki dosyalarınızı tek merkezden yönetin.</p>
        </div>
        @can('create', App\Models\CaseFile::class)
            <a class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800" href="{{ route('case-files.create') }}">Yeni Hukuki Dosya</a>
        @endcan
    </div>

    <div class="mt-6 flex gap-2 overflow-x-auto pb-1 text-sm">
        <a class="whitespace-nowrap rounded-full px-4 py-2 font-medium {{ !request('status') && !request('category') ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}" href="{{ route('case-files.index') }}">Tüm Dosyalar</a>
        <a class="whitespace-nowrap rounded-full px-4 py-2 font-medium {{ request('status') === 'active' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}" href="{{ route('case-files.index', ['status' => 'active']) }}">Aktif</a>
        <a class="whitespace-nowrap rounded-full px-4 py-2 font-medium {{ request('status') === 'closed' ? 'bg-slate-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}" href="{{ route('case-files.index', ['status' => 'closed']) }}">Kapalı</a>
        <a class="whitespace-nowrap rounded-full px-4 py-2 font-medium {{ request('category') === 'lawsuit' ? 'bg-indigo-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}" href="{{ route('case-files.index', ['category' => 'lawsuit']) }}">Dava Dosyaları</a>
        <a class="whitespace-nowrap rounded-full px-4 py-2 font-medium {{ request('category') === 'enforcement' ? 'bg-indigo-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}" href="{{ route('case-files.index', ['category' => 'enforcement']) }}">İcra Dosyaları</a>
    </div>

    <form class="mt-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-5" method="GET" action="{{ route('case-files.index') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Dosya no, başlık veya taraf" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 xl:col-span-2">
        <select name="case_type" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tüm dosya türleri</option>
            @foreach($caseTypes as $caseType)
                <option value="{{ $caseType->id }}" @selected((string) request('case_type') === (string) $caseType->id)>{{ $caseType->name }}</option>
            @endforeach
        </select>
        @if(auth()->user()->isManager())
            <select name="lawyer" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tüm avukatlar</option>
                @foreach($lawyers as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected((string) request('lawyer') === (string) $lawyer->id)>{{ $lawyer->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Filtrele</button>
    </form>

    <div class="mt-6 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Dosya</th><th class="px-5 py-3">Tür</th><th class="px-5 py-3">Durum</th><th class="px-5 py-3">Avukatlar</th><th class="px-5 py-3">Güncelleme</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($caseFiles as $caseFile)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4"><a class="font-semibold text-slate-900 hover:text-indigo-700" href="{{ route('case-files.show', $caseFile) }}">{{ $caseFile->title }}</a><div class="mt-1 font-mono text-xs text-indigo-600">{{ $caseFile->case_no }}</div></td>
                        <td class="px-5 py-4 text-slate-600">{{ $caseFile->caseType->name }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $caseFile->status->badgeClass() }}">{{ $caseFile->status->label() }}</span></td>
                        <td class="px-5 py-4 text-slate-600">{{ $caseFile->activeLawyers->pluck('name')->join(', ') }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $caseFile->updated_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-5 py-14 text-center text-slate-500" colspan="5">Filtrelere uygun hukuki dosya bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 grid gap-3 md:hidden">
        @foreach($caseFiles as $caseFile)
            <a class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" href="{{ route('case-files.show', $caseFile) }}">
                <div class="flex items-start justify-between gap-3"><span class="font-mono text-xs font-bold text-indigo-600">{{ $caseFile->case_no }}</span><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $caseFile->status->badgeClass() }}">{{ $caseFile->status->label() }}</span></div>
                <h2 class="mt-2 font-semibold text-slate-900">{{ $caseFile->title }}</h2>
                <p class="mt-2 text-xs text-slate-500">{{ $caseFile->caseType->name }} · {{ $caseFile->activeLawyers->pluck('name')->join(', ') }}</p>
            </a>
        @endforeach
    </div>

    @if($caseFiles->hasPages())<div class="mt-6">{{ $caseFiles->links() }}</div>@endif
</x-layouts.app>
