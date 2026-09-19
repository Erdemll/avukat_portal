<x-layouts.app :title="$event->event_no">
    <a class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition hover:text-slate-900" href="{{ route('events.index') }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5" />
        </svg>
        Olaylar
    </a>

    <div class="mt-4 grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Event details --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-wrap items-start gap-3">
                            <span class="mt-1 font-mono text-sm font-bold text-indigo-600">{{ $event->event_no }}</span>
                            <h1 class="text-xl font-bold text-slate-900">{{ $event->title }}</h1>
                        </div>
                        @can('create', App\Models\CaseFile::class)
                            @if($event->caseFiles->isEmpty())
                                <a class="rounded-lg bg-indigo-700 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-800" href="{{ route('events.case-file.create', $event) }}">Hukuki Dosyaya Dönüştür</a>
                            @else
                                <a class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100" href="{{ route('case-files.show', $event->caseFiles->first()) }}">Bağlı Dosyayı Gör</a>
                            @endif
                        @endcan
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->system_status->badgeClass() }}">{{ $event->system_status->label() }}</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->priority->badgeClass() }}">{{ $event->priority->label() }}</span>
                        <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $event->eventType->name }}</span>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $event->description }}</p>

                    <dl class="mt-6 grid gap-4 rounded-lg bg-slate-50 p-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Oluşturan</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $event->creator->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Atanan Avukat</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $event->assignedLawyer->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Olay Tarihi</dt>
                            <dd class="mt-1 text-slate-700">{{ $event->occurred_at?->format('d.m.Y H:i') ?? 'Belirtilmedi' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Atanma Tarihi</dt>
                            <dd class="mt-1 text-slate-700">{{ $event->assigned_at?->format('d.m.Y H:i') ?? 'Belirtilmedi' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Oluşturulma</dt>
                            <dd class="mt-1 text-slate-700">{{ $event->created_at->format('d.m.Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Son Güncelleme</dt>
                            <dd class="mt-1 text-slate-700">{{ $event->updated_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Documents --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                        <h2 class="text-base font-semibold text-slate-900">Belgeler</h2>
                        @if($event->documents->isNotEmpty())
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $event->documents->count() }}</span>
                        @endif
                        <span class="ml-auto text-xs text-slate-400">PDF, DOC, XLS, JPG, PNG, GIF, MP3, MP4, ZIP vb.</span>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($event->documents as $document)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                @php
                                    /* Icons are constant server-side strings, safe from user input */
                                    $icons = [
                                        'image' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V4.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />',
                                        'audio' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 0 1 2.25 12c0-.83.102-1.633.294-2.396C2.776 8.756 3.6 8.25 4.51 8.25H6.75Z" />',
                                        'video' => '<path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />',
                                        'text' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />',
                                    ];
                                    $icon = $icons[str($document->mime_type)->before('/')->toString()] ?? $icons['text'];
                                @endphp
                                <svg class="h-8 w-8 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    {!! $icon !!}
                                </svg>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $document->original_name }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] uppercase">{{ $document->extension }}</span>
                                        <span>{{ $document->human_size }}</span>
                                        <span class="text-slate-300">·</span>
                                        <span>{{ $document->uploader->name }}</span>
                                        <span class="text-slate-300">·</span>
                                        <span>{{ $document->created_at->format('d.m.Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('documents.download', $document) }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    İndir
                                </a>
                                @can('delete', $document)
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm transition hover:bg-red-50">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            Sil
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-slate-500">Belge bulunmuyor.</div>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 px-6 py-4">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('events.documents.store', $event) }}">
                        @csrf
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700">Belge Ekle</label>
                                <input name="documents[]" type="file" multiple required accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/*,audio/*,video/*,.txt,.rtf"
                                    class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-700 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-indigo-800">
                                <p class="mt-1 text-xs text-slate-400">PDF, DOC, XLS, resim, ses, video (max 50MB)</p>
                            </div>
                            <button class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m1.5-6.75 4.06 4.06a.75.75 0 0 1 0 1.06l-4.06 4.06m4.06-5.12V16.5m-9.66 1.5v-2.25" />
                                </svg>
                                Yükle
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- Process history --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Süreç Geçmişi</h2>
                </div>
                <div class="px-6 py-5">
                    <div class="flow-root space-y-6">
                        @forelse($event->updates as $update)
                            <article class="relative border-l-2 border-indigo-200 pl-6">
                                <div class="absolute -left-2 top-1 h-3 w-3 rounded-full bg-indigo-600 ring-4 ring-white"></div>
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $update->title ?: 'Süreç güncellemesi' }}</h3>
                                        <span class="text-xs text-slate-500">{{ $update->created_at->format('d.m.Y H:i') }}</span>
                                    </div>
                                    @if($update->savcilik)
                                        <p class="mt-1 text-xs text-slate-500">
                                            <svg class="inline-block h-3.5 w-3.5 -mt-0.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" />
                                            </svg>
                                            {{ $update->savcilik }}
                                        </p>
                                    @endif
                                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $update->description }}</p>
                                    <p class="mt-2 text-xs text-slate-500">{{ $update->user->name }}</p>

                                    @if($update->documents->isNotEmpty())
                                        <div class="mt-3 space-y-2">
                                            <p class="text-xs font-medium text-slate-600">Ek Belgeler</p>
                                            @foreach($update->documents as $document)
                                                <div class="flex items-center gap-2">
                                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                    </svg>
                                                    <a class="text-sm text-indigo-600 hover:underline" href="{{ route('documents.download', $document) }}">
                                                        {{ $document->original_name }}
                                                    </a>
                                                    <span class="text-xs text-slate-400">{{ $document->human_size }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="py-8 text-center text-sm text-slate-500">Henüz süreç güncellemesi yok.</div>
                        @endforelse
                    </div>

                    @can('createUpdate', $event)
                        <form class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-5" method="POST" enctype="multipart/form-data" action="{{ route('events.updates.store', $event) }}">
                            @csrf
                            <h3 class="text-sm font-semibold text-slate-900">Süreç Güncellemesi Ekle</h3>
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label for="update_title" class="block text-sm font-medium text-slate-700">Başlık</label>
                                    <input id="update_title" name="title" placeholder="Kısa başlık"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="update_savcilik" class="block text-sm font-medium text-slate-700">Savcılık</label>
                                    <input id="update_savcilik" name="savcilik" placeholder="Örn: İstanbul Anadolu 3. Asliye Ceza Savcılığı"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="update_description" class="block text-sm font-medium text-slate-700">Açıklama <span class="text-red-500">*</span></label>
                                    <textarea id="update_description" name="description" rows="3" required placeholder="Yapılan işlem ve gelişmeleri yazın"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                                <div>
                                    <label for="update_documents" class="block text-sm font-medium text-slate-700">Ek Belgeler</label>
                                    <input id="update_documents" name="documents[]" type="file" multiple accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/*,audio/*,video/*,.txt,.rtf"
                                        class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-700 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-indigo-800">
                                    <p class="mt-1 text-xs text-slate-400">Resim, ses, video, belge (max 50MB)</p>
                                </div>
                                <button class="inline-flex w-fit items-center gap-1.5 rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Süreç Güncellemesi Kaydet
                                </button>
                            </div>
                        </form>
                    @endcan
                </div>
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            {{-- Current process --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Mevcut Süreç</h2>
                </div>
                <div class="px-5 py-4">
                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $event->current_process ?: 'Henüz güncelleme yok.' }}</p>
                </div>
            </section>

            {{-- Event management --}}
            @can('update', $event)
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">Olay Yönetimi</h2>
                    </div>
                    <div class="px-5 py-4 space-y-4">
                        <form method="POST" action="{{ route('events.status.update', $event) }}">
                            @csrf @method('PATCH')
                            <label class="block text-sm font-medium text-slate-700">Durum Değiştir</label>
                            <div class="mt-2 flex gap-2">
                                <select name="status"
                                    class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach(App\EventStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($event->system_status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-indigo-700 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">
                                    Güncelle
                                </button>
                            </div>
                        </form>

                        <div class="border-t border-slate-100"></div>

                        <form method="POST" action="{{ route('events.priority.update', $event) }}">
                            @csrf @method('PATCH')
                            <label class="block text-sm font-medium text-slate-700">Öncelik Değiştir</label>
                            <div class="mt-2 flex gap-2">
                                <select name="priority"
                                    class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach(App\EventPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}" @selected($event->priority === $priority)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-indigo-700 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">
                                    Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endcan

            {{-- Lawyer change --}}
            @if(auth()->user()->isManager())
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">Avukat Değiştir</h2>
                    </div>
                    <div class="px-5 py-4">
                        <form method="POST" action="{{ route('events.lawyer.update', $event) }}">
                            @csrf @method('PATCH')
                            <label for="lawyer_select" class="block text-sm font-medium text-slate-700">Atanan Avukat</label>
                            <div class="mt-2 flex gap-2">
                                <select id="lawyer_select" name="assigned_lawyer_id"
                                    class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($lawyers as $lawyer)
                                        <option value="{{ $lawyer->id }}" @selected($event->assigned_lawyer_id === $lawyer->id)>{{ $lawyer->name }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-indigo-700 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">
                                    Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
