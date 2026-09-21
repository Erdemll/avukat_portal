<x-layouts.app :title="$document->title ?: $document->original_name">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700" href="{{ route('legal-documents.index', ['case_file' => $document->case_file_id]) }}">Evraklara dön</a>
            <h1 class="mt-2 break-words text-2xl font-semibold text-slate-950 sm:text-3xl">{{ $document->title ?: $document->original_name }}</h1>
            <p class="mt-2 text-sm text-slate-500">UYAP UDF belgesi · Salt okunur güvenli görünüm</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('editUdf', $document)
                @if($data['compatibility'] !== 'unsupported')
                    <a class="rounded-lg bg-indigo-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700" href="{{ route('documents.udf.edit', $document) }}">Düzenle</a>
                @endif
            @endcan
            <a class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="{{ route('documents.udf.download', $document) }}">UDF İndir</a>
        </div>
    </div>

    <dl class="mt-6 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2 xl:grid-cols-3">
        <div class="bg-white p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Hukuki dosya</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $document->caseFile->case_no }}</dd></div>
        <div class="bg-white p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Sürüm</dt><dd class="mt-1 text-sm font-semibold text-slate-900">v{{ $data['version']->version_no }}</dd></div>
        <div class="bg-white p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Yükleyen</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $data['version']->uploader->name }}</dd></div>
        <div class="bg-white p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Tarih</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $data['version']->created_at->format('d.m.Y H:i') }}</dd></div>
        <div class="bg-white p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">İmza durumu</dt>
            <dd class="mt-1 text-sm font-semibold {{ $data['signature_status'] === 'unsigned' ? 'text-slate-700' : 'text-amber-700' }}">
                {{ match ($data['signature_status']) {
                    'signed_or_signature_detected' => 'İmza bilgisi algılandı; geçerlilik doğrulanmadı',
                    'signature_invalidated_by_edit' => 'Düzenlenmiş kopya; imza geçerli sayılmaz',
                    default => 'İmza bilgisi algılanmadı',
                } }}
            </dd>
        </div>
        <div class="bg-white p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Uyumluluk</dt><dd class="mt-1 text-sm font-semibold {{ $data['compatibility'] === 'full' ? 'text-emerald-700' : ($data['compatibility'] === 'partial' ? 'text-amber-700' : 'text-red-700') }}">{{ ['full' => 'Tam', 'partial' => 'Kısmi', 'unsupported' => 'Desteklenmiyor'][$data['compatibility']] }}</dd></div>
    </dl>

    @if(in_array($data['signature_status'], ['signed_or_signature_detected', 'signature_invalidated_by_edit'], true))
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="alert">
            <strong class="block">{{ $data['signature_status'] === 'signature_invalidated_by_edit' ? 'Düzenlenmiş kopyadaki imza geçerli kabul edilmez' : 'Elektronik imza bilgisi algılandı' }}</strong>
            {{ $data['signature_status'] === 'signature_invalidated_by_edit' ? 'Arşiv bütünlüğü için imza girdisi korunmuştur; ancak içerik değiştiği için bu sürüm yeniden elektronik imza gerektirebilir.' : 'Bu belge üzerinde yapılacak değişiklikler mevcut elektronik imzanın geçerliliğini ortadan kaldırabilir. Düzenlenen belge yeni bir UDF sürümü olarak oluşturulur ve yeniden elektronik imza gerektirebilir.' }}
        </div>
    @endif

    @if($data['compatibility'] !== 'full')
        <div @class([
            'mt-5 rounded-xl border p-4 text-sm',
            'border-amber-200 bg-amber-50 text-amber-900' => $data['compatibility'] === 'partial',
            'border-red-200 bg-red-50 text-red-900' => $data['compatibility'] === 'unsupported',
        ])>
            <strong>{{ $data['compatibility'] === 'partial' ? 'Belge kısmi uyumlulukla açıldı.' : 'Bu belge güvenli biçimde düzenlenemez.' }}</strong>
            @if($data['unsupported_nodes'] !== [])<p class="mt-1 break-words text-xs">Tanımlanamayan yapılar: {{ implode(', ', $data['unsupported_nodes']) }}</p>@endif
        </div>
    @endif

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-6"><h2 class="text-sm font-semibold text-slate-800">Belge görüntüsü</h2></div>
        <div class="overflow-x-auto bg-slate-100 p-3 sm:p-6">
            <div class="udf-paper mx-auto" data-udf-editor data-mode="readonly" data-content-source="udf-document-content"></div>
        </div>
    </section>

    <script nonce="{{ Vite::cspNonce() }}" id="udf-document-content" type="application/json">{!! json_encode($data['content'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
</x-layouts.app>
