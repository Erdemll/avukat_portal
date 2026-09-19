<x-layouts.app title="UDF Belgesi Açılamadı">
    <div class="mx-auto max-w-2xl rounded-xl border border-red-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-red-700">UDF işleme hatası</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-950">Belge güvenli biçimde açılamadı</h1>
        <p class="mt-4 text-sm leading-6 text-slate-600">{{ $exception->getMessage() }}</p>
        <p class="mt-3 text-xs text-slate-500">Orijinal dosya değiştirilmedi. İsterseniz mevcut UDF dosyasını indirip resmi UYAP Doküman Editörü ile kontrol edebilirsiniz.</p>
        <div class="mt-6 flex flex-col gap-2 sm:flex-row">
            <a class="rounded-lg bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white" href="{{ route('documents.udf.download', $document) }}">UDF İndir</a>
            <a class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700" href="{{ route('legal-documents.index', ['case_file' => $document->case_file_id]) }}">Evraklara Dön</a>
        </div>
    </div>
</x-layouts.app>
