<x-layouts.app title="Gelişmiş Arama">
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Tek noktadan erişim</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Gelişmiş arama</h1>
    </div>

    <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6" method="GET">
        <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700" for="q">Arama</label>
            <input class="mt-1 w-full rounded-lg border-slate-300" id="q" name="q" value="{{ $term }}" minlength="2" placeholder="Dosya no, kişi, evrak, mahkeme...">
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700" for="type">Kayıt türü</label>
            <select class="mt-1 w-full rounded-lg border-slate-300" id="type" name="type">
                @foreach(['all' => 'Tümü', 'case_files' => 'Dosyalar', 'documents' => 'Evraklar', 'clients' => 'Müvekkiller', 'calendar' => 'Takvim kayıtları'] as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700" for="date_from">Başlangıç</label>
            <input class="mt-1 w-full rounded-lg border-slate-300" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700" for="date_to">Bitiş</label>
            <input class="mt-1 w-full rounded-lg border-slate-300" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
        </div>
        <div class="flex items-end">
            <button class="w-full rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-800">Ara</button>
        </div>
    </form>

    @if($term === '')
        <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Aramayı başlatmak için en az iki karakter yazın.</div>
    @else
        @php($total = $caseFiles->count() + $documents->count() + $clients->count() + $hearings->count() + $deadlines->count() + $tasks->count())
        <p class="mt-5 text-sm text-slate-600"><strong>{{ $total }}</strong> sonuç gösteriliyor (her grupta en fazla 10 kayıt).</p>
        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            @foreach([
                ['Dosyalar', $caseFiles, fn($item) => route('case-files.show', $item), fn($item) => $item->case_no.' · '.$item->title, fn($item) => $item->caseType->name],
                ['Evraklar', $documents, fn($item) => route('documents.download', $item), fn($item) => $item->title ?: $item->original_name, fn($item) => $item->caseFile?->case_no ?? 'Hukuki talep evrakı'],
                ['Müvekkiller', $clients, fn($item) => route('clients.show', $item), fn($item) => $item->party->display_name, fn($item) => $item->party->email ?: 'E-posta bilgisi yok'],
            ] as [$heading, $items, $url, $primary, $secondary])
                @if($items->isNotEmpty())
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <h2 class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-900">{{ $heading }}</h2>
                        <ul class="divide-y divide-slate-100">
                            @foreach($items as $item)
                                <li><a class="block px-4 py-3 hover:bg-slate-50" href="{{ $url($item) }}"><span class="block text-sm font-medium text-indigo-700">{{ $primary($item) }}</span><span class="mt-1 block text-xs text-slate-500">{{ $secondary($item) }}</span></a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endforeach

            @if($hearings->isNotEmpty() || $deadlines->isNotEmpty() || $tasks->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <h2 class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-900">Takvim ve görevler</h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach($hearings as $item)<li><a class="block px-4 py-3 hover:bg-slate-50" href="{{ route('hearings.edit', $item) }}"><span class="text-sm font-medium text-indigo-700">Duruşma · {{ $item->title }}</span><span class="ml-2 text-xs text-slate-500">{{ $item->hearing_at->format('d.m.Y H:i') }}</span></a></li>@endforeach
                        @foreach($deadlines as $item)<li><a class="block px-4 py-3 hover:bg-slate-50" href="{{ route('deadlines.edit', $item) }}"><span class="text-sm font-medium text-indigo-700">Süre · {{ $item->title }}</span><span class="ml-2 text-xs text-slate-500">{{ $item->due_at->format('d.m.Y H:i') }}</span></a></li>@endforeach
                        @foreach($tasks as $item)<li><a class="block px-4 py-3 hover:bg-slate-50" href="{{ route('legal-tasks.edit', $item) }}"><span class="text-sm font-medium text-indigo-700">Görev · {{ $item->title }}</span><span class="ml-2 text-xs text-slate-500">{{ $item->due_at?->format('d.m.Y H:i') ?? 'Tarih yok' }}</span></a></li>@endforeach
                    </ul>
                </section>
            @endif
        </div>
        @if($total === 0)<div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Arama ölçütlerine uygun kayıt bulunamadı.</div>@endif
    @endif
</x-layouts.app>
