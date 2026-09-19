<x-layouts.app title="Kullanıcılar">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Kullanıcılar</h1>
            <p class="mt-1 text-sm text-slate-500">Sistem kullanıcılarını yönetin.</p>
        </div>
        <a class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800" href="{{ route('admin.users.create') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
            </svg>
            Yeni Kullanıcı
        </a>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-[64rem] divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-medium text-slate-500">Ad</th>
                    <th class="px-4 py-3 font-medium text-slate-500">E-posta</th>
                    <th class="px-4 py-3 font-medium text-slate-500">TC Kimlik No</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Telefon</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Rol</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Durum</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Son Giriş</th>
                    <th class="px-4 py-3 font-medium text-slate-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-slate-600">{{ $user->tc_kimlik_no ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $user->phone ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $user->role->name }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $user->last_login_at?->format('d.m.Y H:i') ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('admin.users.edit', $user) }}">
                                Düzenle
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-12 text-center text-slate-500" colspan="8">Henüz kullanıcı bulunmuyor.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-6">{{ $users->links() }}</div>
    @endif
</x-layouts.app>
