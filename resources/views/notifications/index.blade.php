<x-layouts.app title="Bildirimler">
    <div class="mx-auto max-w-3xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Bildirimler</h1>
                <p class="mt-1 text-sm text-slate-500">Tüm sistem bildirimleriniz.</p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Tümünü Okundu İşaretle
                </button>
            </form>
        </div>

        <div class="mt-6 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @forelse($notifications as $notification)
                <div class="group flex items-start justify-between gap-4 px-6 py-4 transition hover:bg-slate-50 {{ is_null($notification->read_at) ? 'bg-indigo-50/30' : '' }}">
                    <a class="flex-1" href="{{ route('notifications.open', $notification) }}">
                        <p class="text-sm {{ is_null($notification->read_at) ? 'font-semibold text-slate-900' : 'text-slate-600' }}">
                            {{ $notification->data['message'] ?? 'Bildirim' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ is_null($notification->read_at) ? 'Okunmadı' : 'Okundu' }} · {{ $notification->created_at->format('d.m.Y H:i') }}</p>
                    </a>
                    @if(is_null($notification->read_at))
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                                Okundu
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">Bildirim bulunmuyor.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.app>
