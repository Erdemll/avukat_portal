<x-layouts.app title="Olaylar">
    @php($user = auth()->user())

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Olaylar</h1>
            <p class="mt-1 text-sm text-slate-500">Erişim yetkinize uygun olay kayıtları.</p>
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

    {{-- Filters --}}
    <details class="mt-6 group" {{ request()->hasAny(['search', 'event_type', 'status', 'priority', 'assigned_lawyer', 'creator', 'date_from', 'date_to']) ? 'open' : '' }}>
        <summary class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
            Filtreler
            @if(request()->hasAny(['search', 'event_type', 'status', 'priority', 'assigned_lawyer', 'creator', 'date_from', 'date_to']))
                <span class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                    {{ collect([request('search'), request('event_type'), request('status'), request('priority'), request('assigned_lawyer'), request('creator'), request('date_from'), request('date_to')])->filter()->count() }}
                </span>
            @endif
        </summary>
        <form class="mt-3 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4" method="GET" action="{{ route('events.index') }}">
            <div class="sm:col-span-2">
                <label for="search" class="block text-sm font-medium text-slate-700">Arama</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="No, başlık, oluşturan veya avukat"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="event_type" class="block text-sm font-medium text-slate-700">Olay Türü</label>
                <select id="event_type" name="event_type"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tümü</option>
                    @foreach($eventTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) $type->id === request('event_type'))>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Durum</label>
                <select id="status" name="status"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tümü</option>
                    @foreach(App\EventStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($status->value === request('status'))>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="priority" class="block text-sm font-medium text-slate-700">Öncelik</label>
                <select id="priority" name="priority"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tümü</option>
                    @foreach(App\EventPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected($priority->value === request('priority'))>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>
            @if(! $user->isLawyer())
                <div>
                    <label for="assigned_lawyer" class="block text-sm font-medium text-slate-700">Atanan Avukat</label>
                    <select id="assigned_lawyer" name="assigned_lawyer"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tümü</option>
                        @foreach($lawyers as $lawyer)
                            <option value="{{ $lawyer->id }}" @selected((string) $lawyer->id === request('assigned_lawyer'))>{{ $lawyer->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($user->isManager() || $user->isLawyer())
                <div>
                    <label for="creator" class="block text-sm font-medium text-slate-700">Oluşturan</label>
                    <select id="creator" name="creator"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tümü</option>
                        @foreach($creators as $creator)
                            <option value="{{ $creator->id }}" @selected((string) $creator->id === request('creator'))>{{ $creator->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label for="date_from" class="block text-sm font-medium text-slate-700">Başlangıç Tarihi</label>
                <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-slate-700">Bitiş Tarihi</label>
                <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="sort" class="block text-sm font-medium text-slate-700">Sıralama</label>
                <select id="sort" name="sort"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="updated_at" @selected(request('sort', 'updated_at') === 'updated_at')>Son güncelleme</option>
                    <option value="created_at" @selected(request('sort') === 'created_at')>Oluşturulma</option>
                    <option value="event_no" @selected(request('sort') === 'event_no')>Olay no</option>
                    <option value="priority" @selected(request('sort') === 'priority')>Öncelik</option>
                </select>
            </div>
            <div>
                <label for="direction" class="block text-sm font-medium text-slate-700">Yön</label>
                <select id="direction" name="direction"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="desc" @selected(request('direction', 'desc') === 'desc')>Azalan</option>
                    <option value="asc" @selected(request('direction') === 'asc')>Artan</option>
                </select>
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    Filtrele
                </button>
                @if(request()->hasAny(['search', 'event_type', 'status', 'priority', 'assigned_lawyer', 'creator', 'date_from', 'date_to']))
                    <a class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('events.index') }}">
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </details>

    {{-- Desktop table --}}
    <div class="mt-6 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-medium text-slate-500">No</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Başlık</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Tür</th>
                    @if(! $user->isEmployee())
                        <th class="px-4 py-3 font-medium text-slate-500">Oluşturan</th>
                    @endif
                    @if(! $user->isLawyer())
                        <th class="px-4 py-3 font-medium text-slate-500">Avukat</th>
                    @endif
                    <th class="px-4 py-3 font-medium text-slate-500">Durum</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Öncelik</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Son Güncelleme</th>
                    <th class="px-4 py-3 font-medium text-slate-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($events as $event)
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-medium text-indigo-600">{{ $event->event_no }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ Str::limit($event->title, 50) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $event->eventType->name }}</td>
                        @if(! $user->isEmployee())
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $event->creator->name }}</td>
                        @endif
                        @if(! $user->isLawyer())
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $event->assignedLawyer->name }}</td>
                        @endif
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->system_status->badgeClass() }}">{{ $event->system_status->label() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->priority->badgeClass() }}">{{ $event->priority->label() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $event->updated_at->format('d.m.Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('events.show', $event) }}">
                                Aç
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-12 text-center text-slate-500" colspan="9">
                            @if(request()->hasAny(['search', 'event_type', 'status', 'priority', 'assigned_lawyer', 'creator', 'date_from', 'date_to']))
                                Bu filtrelere uygun olay bulunamadı.
                            @elseif($user->isEmployee())
                                Henüz oluşturduğunuz bir olay bulunmuyor.
                            @else
                                Gösterilecek olay bulunmuyor.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="mt-6 grid gap-3 md:hidden">
        @forelse($events as $event)
            <a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300" href="{{ route('events.show', $event) }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-xs font-medium text-indigo-600">{{ $event->event_no }}</p>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ Str::limit($event->title, 40) }}</h2>
                    </div>
                    <span class="text-xs font-medium text-indigo-600">Aç →</span>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Durum</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $event->system_status->badgeClass() }}
                                                                                                                                                                                                                                                                                    ">{{ $event->system_status->label() }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Öncelik</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $event->priority->badgeClass() }}
                                                                                                                                                                                                                                                ">{{ $event->priority->label() }}</span>
                        </dd>
                    </div>
                    @if(! $user->isLawyer())
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Avukat</dt>
                            <dd class="text-slate-700">{{ $event->assignedLawyer->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Güncelleme</dt>
                        <dd class="text-slate-700">{{ $event->updated_at->format('d.m.Y H:i') }}</dd>
                    </div>
                </dl>
            </a>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                @if(request()->hasAny(['search', 'event_type', 'status', 'priority', 'assigned_lawyer', 'creator', 'date_from', 'date_to']))
                    Bu filtrelere uygun olay bulunamadı.
                @else
                    Gösterilecek olay bulunmuyor.
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($events->hasPages())
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
</x-layouts.app>
