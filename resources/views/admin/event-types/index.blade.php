<x-layouts.app title="Olay Türleri">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Olay Türleri</h1>
            <p class="mt-1 text-sm text-slate-500">Olay kategorilerini yönetin.</p>
        </div>
        <a class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800" href="{{ route('admin.event-types.create') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Yeni Tür
        </a>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form class="flex flex-wrap gap-3" method="GET" action="{{ route('admin.event-types.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Ad veya slug ara"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select name="status"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">Tümü</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Pasif</option>
            </select>
            <button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">Filtrele</button>
            @if(request()->hasAny(['search', 'status']))
                <a class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('admin.event-types.index') }}">Temizle</a>
            @endif
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-medium text-slate-500">Ad</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Slug</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Açıklama</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Durum</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Oluşturulma</th>
                    <th class="px-4 py-3 font-medium text-slate-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($eventTypes as $eventType)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $eventType->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $eventType->slug }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ Str::limit($eventType->description, 60) ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $eventType->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $eventType->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $eventType->created_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3">
                            <a class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('admin.event-types.edit', $eventType) }}">
                                Düzenle
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-12 text-center text-slate-500" colspan="6">
                            {{ request()->hasAny(['search', 'status']) ? 'Bu filtrelere uygun olay türü bulunamadı.' : 'Henüz olay türü bulunmuyor.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($eventTypes->hasPages())
        <div class="mt-6">{{ $eventTypes->links() }}</div>
    @endif
</x-layouts.app>
