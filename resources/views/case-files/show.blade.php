@php
    $leadAssignment = $caseFile->assignments->first(fn ($assignment) => is_null($assignment->ended_at) && $assignment->role === App\CaseAssignmentRole::Lead);
    $primaryProceeding = $caseFile->proceedings->first();
    $activeLawyerIds = $caseFile->activeLawyers->pluck('id');
    $statusOptions = $caseFile->status === App\CaseFileStatus::Closed && !auth()->user()->isManager()
        ? collect([$caseFile->status])
        : collect([$caseFile->status, ...$caseFile->status->transitions()])->unique(fn ($status) => $status->value);
@endphp
<x-layouts.app :title="$caseFile->case_no">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-medium text-slate-500 hover:text-slate-900" href="{{ route('case-files.index') }}">&larr; Dosyalarım</a>
            <div class="mt-3 flex flex-wrap items-center gap-3"><span class="font-mono text-sm font-bold text-indigo-600">{{ $caseFile->case_no }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $caseFile->status->badgeClass() }}">{{ $caseFile->status->label() }}</span><span class="rounded-md bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $caseFile->caseType->name }}</span></div>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ $caseFile->title }}</h1>
            <p class="mt-2 text-sm text-slate-500">Lider: {{ $leadAssignment?->lawyer?->name ?? 'Belirlenmedi' }} · Açılış: {{ $caseFile->opened_at->format('d.m.Y') }}</p>
        </div>
        @if($canUpdateCase)<a class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" href="{{ route('case-files.edit', $caseFile) }}">Genel Bilgileri Düzenle</a>@endif
    </div>

    <div class="mt-6 flex gap-2 overflow-x-auto border-b border-slate-200 text-sm font-medium"><a class="border-b-2 border-indigo-600 px-3 py-3 text-indigo-700" href="#genel">Genel Bilgiler</a><a class="px-3 py-3 text-slate-500" href="#taraflar">Taraflar</a><a class="px-3 py-3 text-slate-500" href="#evraklar">Evraklar</a><a class="px-3 py-3 text-slate-500" href="#durusmalar">Duruşmalar</a><a class="px-3 py-3 text-slate-500" href="#sureler">Süreler</a><a class="px-3 py-3 text-slate-500" href="#gorevler">Görevler</a><a class="px-3 py-3 text-slate-500" href="#tebligatlar">Tebligatlar</a><a class="px-3 py-3 text-slate-500" href="#finans">Finans</a><a class="px-3 py-3 text-slate-500" href="#iletisim">İletişim</a></div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        <div class="space-y-6">
            <section id="genel" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Genel Bilgiler</h2></div>
                <div class="px-6 py-5">
                    <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $caseFile->description ?: 'Açıklama girilmemiş.' }}</p>
                    <dl class="mt-6 grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Öncelik</dt><dd class="mt-1 font-medium text-slate-900">{{ $caseFile->priority->label() }}</dd></div>
                        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Oluşturan</dt><dd class="mt-1 font-medium text-slate-900">{{ $caseFile->creator->name }}</dd></div>
                        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Kapanış</dt><dd class="mt-1 font-medium text-slate-900">{{ $caseFile->closed_at?->format('d.m.Y') ?? '-' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Mahkeme / İcra Süreci</h2></div>
                <div class="px-6 py-5 text-sm">
                    @if($primaryProceeding)
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><div><span class="text-slate-500">Tür</span><p class="mt-1 font-medium">{{ $primaryProceeding->type->label() }}</p></div><div><span class="text-slate-500">Adliye</span><p class="mt-1 font-medium">{{ $primaryProceeding->courthouse ?: '-' }}</p></div><div><span class="text-slate-500">Makam</span><p class="mt-1 font-medium">{{ $primaryProceeding->authority_name ?: '-' }}</p></div><div><span class="text-slate-500">Esas</span><p class="mt-1 font-medium">{{ $primaryProceeding->principal_year && $primaryProceeding->principal_number ? $primaryProceeding->principal_year.'/'.$primaryProceeding->principal_number : '-' }}</p></div><div><span class="text-slate-500">Karar</span><p class="mt-1 font-medium">{{ $primaryProceeding->decision_year && $primaryProceeding->decision_number ? $primaryProceeding->decision_year.'/'.$primaryProceeding->decision_number : '-' }}</p></div><div><span class="text-slate-500">Harici No</span><p class="mt-1 font-medium">{{ $primaryProceeding->external_file_number ?: '-' }}</p></div></div>
                    @else
                        <p class="text-slate-500">Henüz mahkeme veya icra bilgisi girilmemiş.</p>
                    @endif
                </div>
            </section>

            <section id="taraflar" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Taraflar</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse($caseFile->activeParties as $party)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4"><div><p class="font-medium text-slate-900">{{ $party->display_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $party->pivot->role->label() }} · {{ $party->pivot->side->label() }}</p></div>@if($canManageParties)<form method="POST" action="{{ route('case-files.parties.destroy', [$caseFile, $party->pivot->id]) }}">@csrf @method('DELETE')<button class="text-xs font-medium text-red-600 hover:text-red-800">İlişkiyi Sonlandır</button></form>@endif</div>
                    @empty
                        <p class="px-6 py-8 text-sm text-slate-500">Dosyaya taraf eklenmemiş.</p>
                    @endforelse
                </div>
                @if($canManageParties)
                    <form class="grid gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5 md:grid-cols-2" method="POST" action="{{ route('case-files.parties.store', $caseFile) }}">@csrf
                        <div class="md:col-span-2"><p class="text-sm font-semibold text-slate-800">Taraf Ekle</p><p class="text-xs text-slate-500">Mevcut bir taraf seçin veya aşağıda yeni taraf bilgilerini doldurun.</p></div>
                        <select name="party_id" class="rounded-lg border-slate-300"><option value="">Yeni taraf oluştur</option>@foreach($availableParties as $party)<option value="{{ $party->id }}">{{ $party->display_name }}</option>@endforeach</select>
                        <select name="type" class="rounded-lg border-slate-300"><option value="">Yeni taraf türü</option>@foreach(App\PartyType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
                        <input name="name" placeholder="Ad" class="rounded-lg border-slate-300"><input name="surname" placeholder="Soyad" class="rounded-lg border-slate-300"><input name="company_name" placeholder="Şirket unvanı" class="rounded-lg border-slate-300 md:col-span-2">
                        <select name="role" required class="rounded-lg border-slate-300"><option value="">Dosyadaki rolü</option>@foreach(App\CaseFilePartyRole::cases() as $role)<option value="{{ $role->value }}">{{ $role->label() }}</option>@endforeach</select>
                        <select name="side" required class="rounded-lg border-slate-300"><option value="">Taraf yönü</option>@foreach(App\CasePartySide::cases() as $side)<option value="{{ $side->value }}">{{ $side->label() }}</option>@endforeach</select>
                        <button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white md:col-span-2">Tarafı Ekle</button>
                    </form>
                @endif
            </section>

            <section id="evraklar" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4"><div><h2 class="font-semibold text-slate-900">Evraklar</h2><p class="mt-1 text-xs text-slate-500">Değiştirilemez sürüm geçmişiyle özel depolama.</p></div><a class="text-sm font-medium text-indigo-600" href="{{ route('legal-documents.index', ['case_file' => $caseFile->id]) }}">Tümünü Gör</a></div>
                <div class="divide-y divide-slate-100">
                    @forelse($caseFile->documents as $document)
                        <div class="px-6 py-4"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><p class="break-words font-medium text-slate-900">{{ $document->title ?: $document->original_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $document->folder?->name ?? 'Klasörsüz' }} · v{{ $document->currentVersion?->version_no ?? 1 }} · {{ $document->human_size }}</p></div><div class="flex gap-2">@if($document->currentVersion && in_array($document->currentVersion->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true))<a class="text-xs font-medium text-indigo-600" href="{{ route('document-versions.preview', $document->currentVersion) }}" target="_blank">Önizle</a>@endif<a class="text-xs font-medium text-slate-700" href="{{ route('documents.download', $document) }}">İndir</a></div></div>
                        @if($canManageDocuments)<form class="mt-3 flex flex-wrap gap-2" method="POST" enctype="multipart/form-data" action="{{ route('document-versions.store', $document) }}">@csrf<input type="file" name="file" required class="min-w-0 flex-1 text-xs"><input name="change_note" placeholder="Sürüm notu" class="rounded-lg border-slate-300 text-xs"><button class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white">Yeni Sürüm</button></form>@endif
                        </div>
                    @empty
                        <p class="px-6 py-8 text-sm text-slate-500">Henüz evrak yüklenmemiş.</p>
                    @endforelse
                </div>
                @if($canManageDocuments)
                    <div class="grid gap-5 border-t border-slate-100 bg-slate-50 px-6 py-5 lg:grid-cols-[1fr_2fr]">
                        <form method="POST" action="{{ route('case-files.document-folders.store', $caseFile) }}">@csrf<label class="text-xs font-medium text-slate-600">Yeni sanal klasör</label><div class="mt-1 flex gap-2"><input name="name" required placeholder="Klasör adı" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm"><button class="rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium">Ekle</button></div></form>
                        <form class="grid gap-3 sm:grid-cols-2" method="POST" enctype="multipart/form-data" action="{{ route('case-files.documents.store', $caseFile) }}">@csrf<select name="folder_id" class="rounded-lg border-slate-300 text-sm"><option value="">Klasörsüz</option>@foreach($caseFile->documentFolders as $folder)<option value="{{ $folder->id }}">{{ $folder->name }}</option>@endforeach</select><select name="document_type" class="rounded-lg border-slate-300 text-sm"><option value="other">Diğer Evrak</option><option value="petition">Dilekçe</option><option value="evidence">Delil</option><option value="notice">Tebligat</option><option value="report">Rapor</option><option value="contract">Sözleşme</option><option value="invoice">Fatura</option><option value="enforcement">İcra Evrakı</option></select><input name="title" placeholder="Tek dosya için evrak başlığı" class="rounded-lg border-slate-300 text-sm sm:col-span-2"><input type="file" name="documents[]" multiple required class="text-sm sm:col-span-2"><button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white sm:col-span-2">Evrak Yükle</button></form>
                    </div>
                @endif
            </section>

            <section id="durusmalar" class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Duruşmalar</h2>@if($canManageOperations)<a class="text-sm font-medium text-indigo-600" href="{{ route('hearings.create', ['case_file' => $caseFile->id]) }}">Duruşma Ekle</a>@endif</div><div class="divide-y divide-slate-100">@forelse($caseFile->hearings as $hearing)<a class="flex items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50" href="{{ route('hearings.edit', $hearing) }}"><div><p class="font-medium text-slate-900">{{ $hearing->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $hearing->court ?: 'Mahkeme belirtilmedi' }} · {{ $hearing->lawyer?->name ?? 'Avukat belirtilmedi' }}</p></div><time class="whitespace-nowrap text-sm font-semibold text-indigo-700">{{ $hearing->hearing_at->format('d.m.Y H:i') }}</time></a>@empty<p class="px-6 py-8 text-sm text-slate-500">Duruşma kaydı bulunmuyor.</p>@endforelse</div></section>

            <section id="sureler" class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Hukuki Süreler</h2>@if($canManageOperations)<a class="text-sm font-medium text-indigo-600" href="{{ route('deadlines.create', ['case_file' => $caseFile->id]) }}">Süre Ekle</a>@endif</div><div class="divide-y divide-slate-100">@forelse($caseFile->deadlines as $deadline)<a class="flex items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50" href="{{ route('deadlines.edit', $deadline) }}"><div><p class="font-medium text-slate-900">{{ $deadline->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $deadline->assignedLawyer?->name ?? 'Sorumlu belirtilmedi' }} · {{ $deadline->status->label() }}</p></div><time class="whitespace-nowrap text-sm font-semibold {{ $deadline->status === App\DeadlineStatus::Open && $deadline->due_at->isPast() ? 'text-red-700' : 'text-amber-700' }}">{{ $deadline->due_at->format('d.m.Y H:i') }}</time></a>@empty<p class="px-6 py-8 text-sm text-slate-500">Hukuki süre kaydı bulunmuyor.</p>@endforelse</div></section>

            <section id="gorevler" class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Görevler</h2><a class="text-sm font-medium text-indigo-600" href="{{ route('legal-tasks.create', ['case_file' => $caseFile->id]) }}">Görev Ekle</a></div><div class="divide-y divide-slate-100">@forelse($caseFile->tasks as $task)<a class="flex items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50" href="{{ route('legal-tasks.edit', $task) }}"><div><p class="font-medium text-slate-900">{{ $task->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $task->assignee->name }} · {{ $task->status->label() }}</p></div><span class="text-sm text-slate-600">{{ $task->due_at?->format('d.m.Y H:i') ?? 'Tarihsiz' }}</span></a>@empty<p class="px-6 py-8 text-sm text-slate-500">Görev bulunmuyor.</p>@endforelse</div></section>

            <section id="tebligatlar" class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Tebligatlar</h2><a class="text-sm font-medium text-indigo-600" href="{{ route('service-notices.create',['case_file'=>$caseFile->id]) }}">Tebligat Ekle</a></div><div class="divide-y divide-slate-100">@forelse($caseFile->serviceNotices as $notice)<a class="flex justify-between gap-3 px-6 py-4" href="{{ route('service-notices.edit',$notice) }}"><span class="font-medium">{{ $notice->type->label() }}</span><span class="text-sm text-rose-700">{{ $notice->service_date->format('d.m.Y') }}</span></a>@empty<p class="px-6 py-8 text-sm text-slate-500">Tebligat kaydı yok.</p>@endforelse</div></section>

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Arabuluculuk</h2><a class="text-sm font-medium text-indigo-600" href="{{ route('mediations.create',['case_file'=>$caseFile->id]) }}">Kayıt Ekle</a></div><div class="divide-y divide-slate-100">@forelse($caseFile->mediations as $mediation)<a class="flex justify-between gap-3 px-6 py-4" href="{{ route('mediations.edit',$mediation) }}"><span>{{ $mediation->mediation_file_no ?: 'Arabuluculuk süreci' }}</span><span class="text-sm text-teal-700">{{ $mediation->status->label() }}</span></a>@empty<p class="px-6 py-8 text-sm text-slate-500">Arabuluculuk kaydı yok.</p>@endforelse</div></section>

            <section id="finans" class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Tahsilat & Ödeme</h2><a class="text-sm font-medium text-indigo-600" href="{{ route('financial-entries.index',['case_file'=>$caseFile->id]) }}">Tüm Hareketler</a></div><div class="divide-y divide-slate-100">@forelse($caseFile->financialEntries as $entry)<div class="flex justify-between gap-3 px-6 py-4"><div><p class="font-medium">{{ $entry->type->label() }}</p><p class="text-xs text-slate-500">{{ $entry->description }}</p></div><strong>{{ number_format((float)$entry->amount,2,',','.') }} {{ $entry->currency }}</strong></div>@empty<p class="px-6 py-8 text-sm text-slate-500">Finans hareketi yok.</p>@endforelse</div></section>

            <section id="iletisim" class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Müvekkil İletişimleri</h2><a class="text-sm font-medium text-indigo-600" href="{{ route('client-communications.create',['case_file'=>$caseFile->id]) }}">İletişim Ekle</a></div><div class="divide-y divide-slate-100">@forelse($caseFile->clientCommunications as $communication)<a class="block px-6 py-4" href="{{ route('client-communications.show',$communication) }}"><p class="font-medium">{{ $communication->client->party->display_name }} · {{ $communication->subject }}</p><p class="mt-1 text-xs text-slate-500">{{ $communication->type->label() }} · {{ $communication->communication_at->format('d.m.Y H:i') }}</p></a>@empty<p class="px-6 py-8 text-sm text-slate-500">İletişim kaydı yok.</p>@endforelse</div></section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Sorumlu Avukatlar</h2><div class="mt-4 space-y-3">@foreach($caseFile->activeLawyers as $lawyer)<div class="flex items-center justify-between gap-3"><span class="text-sm font-medium text-slate-800">{{ $lawyer->name }}</span><span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">{{ $lawyer->pivot->role->label() }}</span></div>@endforeach</div></section>

            @if($canUpdateCase)
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Dosya Durumu</h2><form class="mt-4 space-y-3" method="POST" action="{{ route('case-files.status.update', $caseFile) }}">@csrf @method('PATCH')<input type="hidden" name="lock_version" value="{{ $caseFile->lock_version }}"><select name="status" class="block w-full rounded-lg border-slate-300">@foreach($statusOptions as $status)<option value="{{ $status->value }}" @selected(old('status', $caseFile->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select><textarea name="reason" rows="2" placeholder="Sonuçlandırma/kapatma gerekçesi" class="block w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea>@error('status')<p class="text-xs text-red-600">{{ $message }}</p>@enderror @error('reason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror<button class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Durumu Güncelle</button></form></section>
            @endif

            @if($canAssignCase)
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Avukat Atamaları</h2><form class="mt-4 space-y-3" method="POST" action="{{ route('case-files.assignments.update', $caseFile) }}">@csrf @method('PUT')<input type="hidden" name="lock_version" value="{{ $caseFile->lock_version }}"><div class="max-h-56 space-y-2 overflow-y-auto">@foreach($lawyers as $lawyer)<label class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 p-2 text-sm"><span><input type="checkbox" name="lawyer_ids[]" value="{{ $lawyer->id }}" @checked($activeLawyerIds->contains($lawyer->id))> {{ $lawyer->name }}</span><span class="text-xs"><input type="radio" name="lead_lawyer_id" value="{{ $lawyer->id }}" @checked($leadAssignment?->lawyer_id === $lawyer->id)> Lider</span></label>@endforeach</div><textarea name="reason" rows="2" placeholder="Atama/değişiklik gerekçesi" class="block w-full rounded-lg border-slate-300"></textarea><button class="w-full rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Atamaları Güncelle</button></form></section>
            @endif

            @if($caseFile->events->isNotEmpty())<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Kaynak Talepler</h2>@foreach($caseFile->events as $event)@can('view', $event)<a class="mt-3 block rounded-lg bg-slate-50 p-3 text-sm font-medium text-indigo-700" href="{{ route('events.show', $event) }}">{{ $event->event_no }} · {{ $event->title }}</a>@endcan @endforeach</section>@endif
        </aside>
    </div>
</x-layouts.app>
