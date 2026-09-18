<x-layouts.app :title="$eventType->exists ? 'Olay Türü Düzenle' : 'Yeni Olay Türü'">
    <a class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition hover:text-slate-900" href="{{ route('admin.event-types.index') }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5" />
        </svg>
        Olay Türleri
    </a>

    <div class="mt-4 max-w-2xl">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h1 class="text-lg font-semibold text-slate-900">{{ $eventType->exists ? 'Olay Türü Düzenle' : 'Yeni Olay Türü Oluştur' }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $eventType->exists ? 'Olay türü bilgilerini güncelleyin.' : 'Yeni bir olay türü tanımlayın.' }}</p>
            </div>

            <form class="px-6 py-5" method="POST" action="{{ $eventType->exists ? route('admin.event-types.update', $eventType) : route('admin.event-types.store') }}">
                @csrf
                @if($eventType->exists)
                    @method('PUT')
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Ad <span class="text-red-500">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $eventType->name) }}" required placeholder="Örn: Yeni Ürün Satışı"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="mt-5">
                    <label for="slug" class="block text-sm font-medium text-slate-700">Slug <span class="text-red-500">*</span></label>
                    <input id="slug" name="slug" value="{{ old('slug', $eventType->slug) }}" required placeholder="ornek-slug"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">
                    <p class="mt-1 text-xs text-slate-500">URL ve kod tarafından kullanılan benzersiz tanımlayıcı. Küçük harf ve tire kullanın.</p>
                </div>

                <div class="mt-5">
                    <label for="description" class="block text-sm font-medium text-slate-700">Açıklama</label>
                    <textarea id="description" name="description" rows="3" placeholder="Bu olay türü hakkında kısa açıklama"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $eventType->description) }}</textarea>
                </div>

                @if($eventType->exists)
                    <div class="mt-5 flex items-center rounded-lg bg-slate-50 p-3">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked($eventType->is_active)
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="ml-2 text-sm text-slate-700">Aktif</label>
                    </div>
                @endif

                <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Kaydet
                    </button>
                    <a class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('admin.event-types.index') }}">
                        İptal
                    </a>
                </div>
            </form>
        </div>

        @if($eventType->exists)
            <div class="mt-4 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="px-6 py-4">
                    <form method="POST" action="{{ $eventType->is_active ? route('admin.event-types.deactivate', $eventType) : route('admin.event-types.activate', $eventType) }}">
                        @csrf
                        <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-slate-50 {{ $eventType->is_active ? 'text-red-700 hover:border-red-300' : 'text-emerald-700 hover:border-emerald-300' }}">
                            {{ $eventType->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
