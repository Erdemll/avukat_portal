@props([
    'title',
    'eyebrow' => 'Güvenli erişim',
    'heading',
    'description' => null,
])

<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#293e47">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full antialiased">
    <main class="min-h-svh lg:grid lg:grid-cols-[minmax(20rem,0.78fr)_minmax(30rem,1.22fr)]">
        <section class="relative hidden overflow-hidden bg-indigo-950 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14" aria-label="Portal bilgisi">
            <div class="absolute -right-28 -top-28 h-80 w-80 rounded-full border border-white/10"></div>
            <div class="absolute -right-10 top-8 h-48 w-48 rounded-full border border-white/10"></div>
            <a class="relative flex items-center gap-3" href="{{ route('login') }}">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg border border-white/20 bg-white/10">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.339A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" /></svg>
                </span>
                <span><span class="block text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/55">Tepenet</span><span class="block font-serif text-xl font-semibold">Avukat Portalı</span></span>
            </a>

            <div class="relative max-w-lg">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-200">Kurumsal hukuk operasyonu</p>
                <h2 class="mt-5 font-serif text-4xl font-semibold leading-tight text-white xl:text-5xl">Hukuki süreçler için güvenli ve düzenli çalışma alanı.</h2>
                <p class="mt-5 max-w-md text-sm leading-7 text-white/65">Dosya, duruşma, süre ve belgelerinizi yetki kontrollü tek bir merkezden yönetin.</p>
            </div>

            <div class="relative flex items-center gap-3 text-xs text-white/45">
                <span class="h-px w-10 bg-white/25"></span>
                <span>Yetkili kullanıcı erişimi</span>
            </div>
        </section>

        <section class="flex min-h-svh items-center justify-center px-4 py-8 sm:px-8 lg:px-12 xl:px-20">
            <div class="w-full max-w-lg">
                <div class="mb-7 flex items-center gap-3 lg:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-900 text-white"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.339A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" /></svg></span>
                    <span><span class="block text-[0.62rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Tepenet</span><span class="font-serif text-lg font-semibold text-slate-900">Avukat Portalı</span></span>
                </div>

                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">{{ $eyebrow }}</p>
                    <h1 class="mt-2 font-serif text-3xl font-semibold text-slate-950 sm:text-4xl">{{ $heading }}</h1>
                    @if($description)<p class="mt-3 max-w-md text-sm leading-6 text-slate-600">{{ $description }}</p>@endif
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-lg sm:p-7">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">Bu sistem yalnızca yetkili kullanıcıların erişimine açıktır.</p>
            </div>
        </section>
    </main>
</body>
</html>
