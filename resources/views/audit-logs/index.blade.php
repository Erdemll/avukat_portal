<x-layouts.app title="Audit Kayıtları">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Audit Kayıtları</h1>
            <p class="mt-1 text-sm text-slate-500">Sistemdeki tüm önemli aksiyonların kaydı.</p>
        </div>
    </div>

    <details class="mt-6 group" {{ request()->hasAny(['action', 'user', 'event', 'from', 'to']) ? 'open' : '' }}>
        <summary class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
            Filtreler
        </summary>
        <form class="mt-3 flex flex-wrap gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" method="GET" action="{{ route('audit-logs.index') }}">
            <input name="action" value="{{ request('action') }}" placeholder="Aksiyon ara"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input name="user" value="{{ request('user') }}" placeholder="Kullanıcı ID"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input name="event" value="{{ request('event') }}" placeholder="Olay ID"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input name="from" type="date" value="{{ request('from') }}"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input name="to" type="date" value="{{ request('to') }}"
                class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">Filtrele</button>
            @if(request()->hasAny(['action', 'user', 'event', 'from', 'to']))
                <a class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('audit-logs.index') }}">Temizle</a>
            @endif
        </form>
    </details>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-[58rem] divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-medium text-slate-500">Tarih</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Kullanıcı</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Aksiyon</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Olay</th>
                    <th class="px-4 py-3 font-medium text-slate-500">IP Adresi</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Açıklama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $log->user?->name ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a class="font-mono text-xs font-medium text-indigo-600 hover:underline" href="{{ route('audit-logs.show', $log) }}">
                                {{ $log->action->value }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($log->event)
                                <a class="font-mono text-xs text-indigo-600 hover:underline" href="{{ route('events.show', $log->event) }}">
                                    {{ $log->event->event_no }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">{{ $log->ip_address ?: '-' }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-slate-600">{{ Str::limit($log->description, 50) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-12 text-center text-slate-500" colspan="6">
                            {{ request()->hasAny(['action', 'user', 'event', 'from', 'to']) ? 'Bu filtrelere uygun kayıt bulunamadı.' : 'Henüz audit kaydı bulunmuyor.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
</x-layouts.app>
