<div>
    <!-- The biggest battle is the war against ignorance. - Mustafa Kemal Atatürk -->
</div>
<x-layouts.app title="Hukuki Dosya Türleri">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Hukuki Dosya Türleri</h1>
            <p class="mt-1 text-sm text-slate-500">Dosya adlarını ve sabit ana kategorilerini yönetin.</p>
        </div>
        <a class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800" href="{{ route('admin.case-types.create') }}">Yeni Dosya Türü</a>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto]" method="GET" action="{{ route('admin.case-types.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Ad veya slug ara" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select name="category" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tüm kategoriler</option>
                @foreach(App\CaseTypeCategory::cases() as $category)
                    <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">Tümü</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Pasif</option>
            </select>
            <button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-800">Filtrele</button>
        </form>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-medium text-slate-500">Ad</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Kategori</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Slug</th>
                    <th class="px-4 py-3 font-medium text-slate-500">Durum</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($caseTypes as $caseType)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $caseType->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $caseType->category->label() }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $caseType->slug }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $caseType->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">{{ $caseType->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                        <td class="px-4 py-3 text-right"><a class="font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('admin.case-types.edit', $caseType) }}">Düzenle</a></td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-12 text-center text-slate-500" colspan="5">Hukuki dosya türü bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($caseTypes->hasPages())
        <div class="mt-6">{{ $caseTypes->links() }}</div>
    @endif
</x-layouts.app>
