<x-layouts.app title="UYAP Manuel Aktarım">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Yönetim aracı</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">UYAP manuel CSV aktarımı</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Dosyayı önce önizleyin. İçe aktarımda aynı dosyayı yeniden seçmeniz gerekir; tüm satırlar geçerliyse işlem tek transaction içinde tamamlanır.</p>

            <form class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('uyap-import.store') }}" enctype="multipart/form-data">
                @csrf
                <label class="block text-sm font-medium text-slate-700" for="file">CSV dosyası (UTF-8, en fazla 2 MB / 500 satır)</label>
                <input class="mt-2 block w-full rounded-lg border border-slate-300 bg-white p-2 text-sm" id="file" name="file" type="file" accept=".csv,.txt,text/csv" required>
                @error('file')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                <div class="mt-4 flex flex-wrap gap-3">
                    <button class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100" name="mode" value="preview">Doğrula ve önizle</button>
                    <button class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-800" name="mode" value="import">Geçerliyse içe aktar</button>
                </div>
            </form>
        </div>

        <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
            <h2 class="font-semibold">Desteklenen sütunlar</h2>
            <p class="mt-2 leading-6">Noktalı virgül veya virgül ayracı kullanılabilir. Başlıklar:</p>
            <code class="mt-3 block break-words rounded-lg bg-white/70 p-3 text-xs leading-5">uyap_referans, dosya_basligi, dosya_turu, oncelik, acilis_tarihi, avukat_eposta, yargi_turu, mahkeme, esas_yili, esas_no</code>
            <p class="mt-3 leading-6">Öncelik: low, normal, high, urgent. Yargı türü: dava, icra, arabuluculuk veya diğer.</p>
        </aside>
    </div>

    @if($preview !== null)
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Doğrulama sonucu</h2><p class="mt-1 text-sm text-slate-500">{{ count($preview['rows']) }} geçerli, {{ count($preview['errors']) }} hatalı kayıt.</p></div>
            @if($preview['errors'] !== [])
                <ul class="space-y-2 border-b border-red-100 bg-red-50 p-5 text-sm text-red-700">@foreach($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            @if($preview['rows'] !== [])
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Satır</th><th class="px-4 py-3 text-left">UYAP ref.</th><th class="px-4 py-3 text-left">Dosya</th><th class="px-4 py-3 text-left">Tür</th><th class="px-4 py-3 text-left">Avukat</th><th class="px-4 py-3 text-left">Esas</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($preview['rows'] as $row)<tr><td class="px-4 py-3">{{ $row['line'] }}</td><td class="px-4 py-3 font-mono text-xs">{{ $row['external_reference'] }}</td><td class="px-4 py-3 font-medium">{{ $row['title'] }}</td><td class="px-4 py-3">{{ $row['case_type_name'] }}</td><td class="px-4 py-3">{{ $row['lawyer_name'] }}</td><td class="px-4 py-3">{{ $row['principal_year'] }}/{{ $row['principal_number'] }}</td></tr>@endforeach</tbody></table></div>
            @endif
        </section>
    @endif
</x-layouts.app>
