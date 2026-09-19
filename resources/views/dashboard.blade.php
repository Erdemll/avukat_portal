<x-layouts.app title="Dashboard">
    @php($user = auth()->user())

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Hukuki olay yönetimine genel bakış.</p>
        </div>
        @can('create', App\Models\Event::class)
            <a class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800" href="{{ route('events.create') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Yeni Olay Oluştur
            </a>
        @endcan
    </div>

    {{-- Stats cards --}}
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-7">
        {{-- Total --}}
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                    <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $user->isLawyer() ? 'Bana Atanan' : 'Toplam' }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts['total'] }}</p>
        </article>

        {{-- Open --}}
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3 3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Açık</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $counts['open'] }}</p>
        </article>

        {{-- In Progress --}}
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Devam Eden</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $counts['in_progress'] }}</p>
        </article>

        {{-- Waiting --}}
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100">
                <svg class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Bekleyen</p>
            <p class="mt-1 text-2xl font-bold text-orange-700">{{ $counts['waiting'] }}</p>
        </article>

        {{-- Resolved --}}
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Çözülen</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $counts['resolved'] }}</p>
        </article>

        {{-- Closed --}}
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Kapalı</p>
            <p class="mt-1 text-2xl font-bold text-slate-700">{{ $counts['closed'] }}</p>
        </article>

        {{-- Urgent --}}
        <article class="rounded-xl border border-red-200 bg-white p-4 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100">
                <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-red-600">Acil</p>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ $counts['urgent'] }}</p>
        </article>
    </div>

    {{-- Recent events --}}
    <section class="mt-8 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-base font-semibold text-slate-900">{{ $user->isManager() ? 'Son Oluşturulan Olaylar' : 'Son Güncellenen Olaylar' }}</h2>
            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('events.index') }}">Tümünü Gör →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentEvents as $event)
                <a class="block px-6 py-4 transition hover:bg-slate-50" href="{{ route('events.show', $event) }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-medium text-indigo-600">{{ $event->event_no }}</span>
                                <span class="text-sm font-medium text-slate-900">{{ $event->title }}</span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span>{{ $event->eventType->name }}</span>
                                <span class="text-slate-300">·</span>
                                <span>{{ $event->updated_at->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->system_status->badgeClass() }}">{{ $event->system_status->label() }}</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->priority->badgeClass() }}">{{ $event->priority->label() }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">Gösterilecek olay bulunmuyor.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Lawyer unprocessed events --}}
    @if($user->isLawyer() && $unprocessedEvents->isNotEmpty())
        <section class="mt-8 rounded-xl border border-amber-200 bg-amber-50 shadow-sm">
            <div class="flex items-center gap-2 border-b border-amber-200/60 px-6 py-4">
                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <h2 class="text-base font-semibold text-amber-900">Henüz İşlem Yapılmamış Olaylar</h2>
            </div>
            <div class="divide-y divide-amber-200/60">
                @forelse($unprocessedEvents as $event)
                    <a class="block px-6 py-4 transition hover:bg-amber-100/50" href="{{ route('events.show', $event) }}">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm font-medium text-indigo-600">{{ $event->event_no }}</span>
                            <span class="text-sm font-medium text-slate-900">{{ $event->title }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-4 text-sm text-amber-700">İşlem bekleyen olay bulunmuyor.</div>
                @endforelse
            </div>
        </section>
    @endif

    {{-- Manager sections --}}
    @if($user->isManager())
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            {{-- Recent updates --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Son Süreç Güncellemeleri</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentUpdates as $update)
                        <a class="block px-6 py-4 transition hover:bg-slate-50" href="{{ route('events.show', $update->event) }}">
                            <p class="text-sm font-medium text-slate-900">{{ $update->title ?: 'Süreç güncellemesi' }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="font-mono font-medium text-indigo-600">{{ $update->event->event_no }}</span>
                                <span class="text-slate-300">·</span>
                                <span>{{ $update->user->name }}</span>
                                <span class="text-slate-300">·</span>
                                <span>{{ $update->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-slate-500">Süreç güncellemesi yok.</div>
                    @endforelse
                </div>
            </section>

            {{-- Lawyer workload --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Avukat İş Yükü</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 font-medium text-slate-500">Avukat</th>
                                <th class="px-6 py-3 font-medium text-slate-500">Aktif Olay</th>
                                <th class="px-6 py-3 font-medium text-slate-500">Acil Olay</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($lawyerWorkloads as $lawyer)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $lawyer->name }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $lawyer->active_events_count }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        @if($lawyer->urgent_events_count > 0)
                                            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">{{ $lawyer->urgent_events_count }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-8 text-center text-sm text-slate-500" colspan="3">Aktif avukat bulunmuyor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</x-layouts.app>
