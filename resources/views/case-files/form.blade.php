@php
    $isEditing = $caseFile->exists;
    $primaryProceeding = $caseFile->relationLoaded('proceedings') ? $caseFile->proceedings->first() : null;
    $selectedLawyers = collect(old('lawyer_ids', []))->map(fn ($id) => (int) $id);
    $selectedClients = collect(old('client_party_ids', []))->map(fn ($id) => (int) $id);
@endphp
<x-layouts.app :title="$isEditing ? 'Hukuki Dosya Düzenle' : 'Yeni Hukuki Dosya'">
    <a class="text-sm font-medium text-slate-500 hover:text-slate-900" href="{{ $sourceEvent ? route('events.show', $sourceEvent) : route('case-files.index') }}">&larr; {{ $sourceEvent ? 'Hukuki Talebe Dön' : 'Dosyalarım' }}</a>
    <div class="mt-4 max-w-5xl">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">{{ $sourceEvent ? $sourceEvent->event_no.' talebinden' : 'Tepenet Hukuk' }}</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">{{ $isEditing ? $caseFile->case_no.' Dosyasını Düzenle' : 'Yeni Hukuki Dosya Oluştur' }}</h1>
            </div>
            <form class="grid gap-6 px-6 py-6" method="POST" action="{{ $isEditing ? route('case-files.update', $caseFile) : ($sourceEvent ? route('events.case-file.store', $sourceEvent) : route('case-files.store')) }}">
                @csrf
                @if($isEditing) @method('PUT') <input type="hidden" name="lock_version" value="{{ $caseFile->lock_version }}"> @endif

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700" for="title">Dosya başlığı</label><input id="title" name="title" required value="{{ old('title', $caseFile->title) }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">@error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700" for="case_type_id">Dosya türü</label><select id="case_type_id" name="case_type_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Seçin</option>@foreach($caseTypes as $caseType)<option value="{{ $caseType->id }}" @selected((string) old('case_type_id', $caseFile->case_type_id) === (string) $caseType->id)>{{ $caseType->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-slate-700" for="opened_at">Açılış tarihi</label><input id="opened_at" type="date" name="opened_at" required value="{{ old('opened_at', $caseFile->opened_at?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                    <div><label class="block text-sm font-medium text-slate-700" for="priority">Öncelik</label><select id="priority" name="priority" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">@foreach(App\EventPriority::cases() as $priority)<option value="{{ $priority->value }}" @selected(old('priority', $caseFile->priority?->value ?? 'normal') === $priority->value)>{{ $priority->label() }}</option>@endforeach</select></div>
                    <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700" for="description">Dosya açıklaması</label><textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $caseFile->description) }}</textarea></div>
                </div>

                @if(!$isEditing)
                    <section class="rounded-xl bg-slate-50 p-5">
                        <h2 class="font-semibold text-slate-900">Avukat Atamaları</h2>
                        @if(auth()->user()->isManager())
                            <p class="mt-1 text-xs text-slate-500">En az bir avukat ve bunlardan bir lider avukat seçin.</p>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($lawyers as $lawyer)
                                    <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-sm"><span><input class="rounded border-slate-300 text-indigo-600" type="checkbox" name="lawyer_ids[]" value="{{ $lawyer->id }}" @checked($selectedLawyers->contains($lawyer->id))> <span class="ml-1">{{ $lawyer->name }}</span></span><span class="whitespace-nowrap text-xs text-slate-500"><input type="radio" name="lead_lawyer_id" value="{{ $lawyer->id }}" @checked((int) old('lead_lawyer_id') === $lawyer->id)> Lider</span></label>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-sm text-slate-600">Dosyanın lider avukatı olarak siz atanacaksınız.</p>
                        @endif
                        @error('lawyer_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror @error('lead_lawyer_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </section>

                    <section><div class="flex items-center justify-between"><h2 class="font-semibold text-slate-900">Müvekkiller</h2><a class="text-sm font-medium text-indigo-600" href="{{ route('clients.create') }}">Yeni müvekkil</a></div><div class="mt-3 grid gap-2 sm:grid-cols-2">@forelse($clients as $client)<label class="rounded-lg border border-slate-200 p-3 text-sm"><input class="rounded border-slate-300 text-indigo-600" type="checkbox" name="client_party_ids[]" value="{{ $client->party_id }}" @checked($selectedClients->contains($client->party_id))> <span class="ml-1 font-medium text-slate-800">{{ $client->party->display_name }}</span></label>@empty<p class="text-sm text-slate-500">Henüz erişilebilir müvekkil bulunmuyor.</p>@endforelse</div></section>
                @endif

                <section class="rounded-xl border border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Mahkeme / İcra Bilgileri</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div><label class="text-sm text-slate-600">Süreç türü</label><select name="proceeding_type" class="mt-1 block w-full rounded-lg border-slate-300">@if(!$primaryProceeding)<option value="">Belirtilmedi</option>@endif @foreach(App\CaseProceedingType::cases() as $type)<option value="{{ $type->value }}" @selected(old('proceeding_type', $primaryProceeding?->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
                        <div><label class="text-sm text-slate-600">Adliye</label><input name="courthouse" value="{{ old('courthouse', $primaryProceeding?->courthouse) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Mahkeme / İcra Müdürlüğü</label><input name="authority_name" value="{{ old('authority_name', $primaryProceeding?->authority_name) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Esas yılı</label><input type="number" name="principal_year" value="{{ old('principal_year', $primaryProceeding?->principal_year) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Esas no</label><input name="principal_number" value="{{ old('principal_number', $primaryProceeding?->principal_number) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Harici dosya no</label><input name="external_file_number" value="{{ old('external_file_number', $primaryProceeding?->external_file_number) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Karar yılı</label><input type="number" name="decision_year" value="{{ old('decision_year', $primaryProceeding?->decision_year) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Karar no</label><input name="decision_number" value="{{ old('decision_number', $primaryProceeding?->decision_number) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                        <div><label class="text-sm text-slate-600">Mahkeme türü</label><input name="court_type" value="{{ old('court_type', $primaryProceeding?->court_type) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
                    </div>
                </section>

                <div class="flex gap-3 border-t border-slate-100 pt-5"><button class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-800">{{ $sourceEvent ? 'Dosyaya Dönüştür' : 'Kaydet' }}</button><a class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700" href="{{ $sourceEvent ? route('events.show', $sourceEvent) : route('case-files.index') }}">İptal</a></div>
            </form>
        </div>
    </div>
</x-layouts.app>
