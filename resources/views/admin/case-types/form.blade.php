<div>
    <!-- Knowing is not enough; we must apply. Being willing is not enough; we must do. - Leonardo da Vinci -->
</div>
<x-layouts.app :title="$caseType->exists ? 'Dosya Türü Düzenle' : 'Yeni Dosya Türü'">
    <a class="text-sm font-medium text-slate-500 transition hover:text-slate-900" href="{{ route('admin.case-types.index') }}">&larr; Hukuki Dosya Türleri</a>

    <div class="mt-4 max-w-2xl rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h1 class="text-lg font-semibold text-slate-900">{{ $caseType->exists ? 'Dosya Türünü Düzenle' : 'Yeni Dosya Türü Oluştur' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Görünen ad yönetilebilir; slug oluşturulduktan sonra sabit kalır.</p>
        </div>

        <form class="grid gap-5 px-6 py-5" method="POST" action="{{ $caseType->exists ? route('admin.case-types.update', $caseType) : route('admin.case-types.store') }}">
            @csrf
            @if($caseType->exists)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Ad</label>
                <input id="name" name="name" value="{{ old('name', $caseType->name) }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-slate-700">Ana kategori</label>
                <select id="category" name="category" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach(App\CaseTypeCategory::cases() as $category)
                        <option value="{{ $category->value }}" @selected(old('category', $caseType->category?->value) === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </select>
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700">Açıklama</label>
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $caseType->description) }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 border-t border-slate-100 pt-5">
                <button class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800">Kaydet</button>
                <a class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route('admin.case-types.index') }}">İptal</a>
            </div>
        </form>
    </div>

    @if($caseType->exists)
        <form class="mt-4" method="POST" action="{{ $caseType->is_active ? route('admin.case-types.deactivate', $caseType) : route('admin.case-types.activate', $caseType) }}">
            @csrf
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium {{ $caseType->is_active ? 'text-red-700 hover:border-red-300' : 'text-emerald-700 hover:border-emerald-300' }}">{{ $caseType->is_active ? 'Pasifleştir' : 'Aktifleştir' }}</button>
        </form>
    @endif
</x-layouts.app>
