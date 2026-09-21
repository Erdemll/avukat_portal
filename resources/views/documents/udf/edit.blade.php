<x-layouts.app :title="'UDF Düzenle · '.($document->title ?: $document->original_name)">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700" href="{{ route('documents.udf.show', $document) }}">Belge görünümüne dön</a>
            <h1 class="mt-2 break-words text-2xl font-semibold text-slate-950 sm:text-3xl">{{ $document->title ?: $document->original_name }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $document->caseFile->case_no }} · Düzenlenen içerik yeni bir sürüm olarak kaydedilir.</p>
        </div>
        <span class="w-fit rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Mevcut sürüm: v{{ $data['version']->version_no }}</span>
    </div>

    @if(in_array($data['signature_status'], ['signed_or_signature_detected', 'signature_invalidated_by_edit'], true))
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="alert">
            <strong class="block">Elektronik imza uyarısı</strong>
            Bu belgede elektronik imza bilgisi bulunuyor olabilir. Değişiklikler mevcut imzanın geçerliliğini ortadan kaldırabilir; yeni sürümün yeniden elektronik imzalanması gerekebilir. Orijinal sürüm korunacaktır.
        </div>
    @endif

    <form id="udf-editor-form" class="mt-6" method="POST" action="{{ route('documents.udf.update', $document) }}" data-version="{{ $data['version']->version_no }}">
        @csrf
        @method('PUT')

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-200 bg-slate-50 p-2 sm:p-3" data-udf-toolbar aria-label="Belge düzenleme araçları">
                <button class="udf-tool-button" type="button" data-editor-command="undo" title="Geri al">↶ <span class="sr-only sm:not-sr-only">Geri al</span></button>
                <button class="udf-tool-button" type="button" data-editor-command="redo" title="Yinele">↷ <span class="sr-only sm:not-sr-only">Yinele</span></button>
                <span class="mx-1 h-7 w-px bg-slate-300"></span>
                <button class="udf-tool-button font-bold" type="button" data-editor-command="bold" title="Kalın">B</button>
                <button class="udf-tool-button italic" type="button" data-editor-command="italic" title="İtalik">I</button>
                <button class="udf-tool-button underline" type="button" data-editor-command="underline" title="Altı çizili">U</button>
                <select class="h-9 min-h-0 w-20 rounded-md border-slate-300 bg-white py-1 text-xs" data-editor-font-size aria-label="Yazı boyutu">
                    @foreach([8, 9, 10, 11, 12, 14, 16, 18, 24, 36] as $size)<option value="{{ $size }}" @selected($size === 12)>{{ $size }} pt</option>@endforeach
                </select>
                <span class="mx-1 hidden h-7 w-px bg-slate-300 sm:block"></span>
                <button class="udf-tool-button" type="button" data-editor-command="align-left" title="Sola hizala">Sol</button>
                <button class="udf-tool-button" type="button" data-editor-command="align-center" title="Ortala">Orta</button>
                <button class="udf-tool-button" type="button" data-editor-command="align-right" title="Sağa hizala">Sağ</button>
                <button class="udf-tool-button" type="button" data-editor-command="align-justify" title="İki yana yasla">Yasla</button>
                <span class="mx-1 hidden h-7 w-px bg-slate-300 md:block"></span>
                <button class="udf-tool-button" type="button" data-editor-command="bullet-list" title="Madde işaretli liste">• Liste</button>
                <button class="udf-tool-button" type="button" data-editor-command="ordered-list" title="Numaralı liste">1. Liste</button>
                <button class="udf-tool-button" type="button" data-editor-command="outdent" title="Girintiyi azalt">− Girinti</button>
                <button class="udf-tool-button" type="button" data-editor-command="indent" title="Girintiyi artır">+ Girinti</button>
                <button class="udf-tool-button" type="button" data-editor-command="table" title="2 × 2 tablo ekle">Tablo</button>
                <button class="udf-tool-button" type="button" data-editor-command="horizontal-rule" title="Yatay ayırıcı">Ayırıcı</button>
            </div>

            <div class="overflow-x-auto bg-slate-100 p-3 sm:p-6">
                <div class="udf-paper mx-auto" data-udf-editor data-mode="edit" data-content-source="udf-document-content"></div>
            </div>
        </section>

        <div class="mt-4 min-h-6 text-sm" data-udf-status aria-live="polite"></div>
        <div class="mt-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700" href="{{ route('documents.udf.show', $document) }}">Vazgeç</a>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700" href="{{ route('documents.udf.download', $document) }}">Mevcut UDF'yi İndir</a>
                <button class="rounded-lg bg-indigo-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60" type="submit" data-udf-submit>Yeni Sürüm Olarak Kaydet</button>
            </div>
        </div>
    </form>

    <script nonce="{{ Vite::cspNonce() }}" id="udf-document-content" type="application/json">{!! json_encode($data['content'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
</x-layouts.app>
