<div>
    <!-- Act only according to that maxim whereby you can, at the same time, will that it should become a universal law. - Immanuel Kant -->
</div>
<x-layouts.app title="Müvekkiller">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Taraf Yönetimi</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Müvekkiller</h1><p class="mt-1 text-sm text-slate-500">Gerçek ve tüzel kişi müvekkil kayıtları.</p></div>@can('create', App\Models\Client::class)<a class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800" href="{{ route('clients.create') }}">Yeni Müvekkil</a>@endcan</div>
    <form class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto_auto]" method="GET" action="{{ route('clients.index') }}"><input name="search" value="{{ request('search') }}" placeholder="Ad, soyad veya şirket ara" class="w-full min-w-0 rounded-lg border-slate-300"><select name="status" class="w-full rounded-lg border-slate-300"><option value="">Tüm durumlar</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Pasif</option></select><button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrele</button></form>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($clients as $client)
            <a class="group min-w-0 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md" href="{{ route('clients.show', $client) }}"><div class="flex items-start justify-between gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 font-bold text-indigo-700">{{ Str::upper(Str::substr($client->party->display_name, 0, 1)) }}</div><span class="rounded-full px-2 py-1 text-xs font-medium {{ $client->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $client->status === 'active' ? 'Aktif' : 'Pasif' }}</span></div><h2 class="mt-4 break-words font-semibold text-slate-900 group-hover:text-indigo-700">{{ $client->party->display_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $client->party->type->label() }}</p><p class="mt-4 break-words text-xs text-slate-500">{{ $client->party->phone ?: 'Telefon yok' }} · {{ $client->party->email ?: 'E-posta yok' }}</p></a>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-12 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Müvekkil kaydı bulunamadı.</div>
        @endforelse
    </div>
    @if($clients->hasPages())<div class="mt-6">{{ $clients->links() }}</div>@endif
</x-layouts.app>
