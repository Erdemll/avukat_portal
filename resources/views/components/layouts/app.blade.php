@props(['title' => null])

@php
    $user = auth()->user();
    $isLegalUser = $user->isManager() || $user->isLawyer();
    $coreNavigation = [
        ['label' => 'Genel Bakış', 'url' => route('dashboard'), 'active' => ['dashboard'], 'icon' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25V21l3.75-2.25L15.75 21v-4.5H18a2.25 2.25 0 0 0 2.25-2.25V3m-16.5 0h16.5m-16.5 0A2.25 2.25 0 0 0 1.5 5.25v9A2.25 2.25 0 0 0 3.75 16.5m16.5-13.5A2.25 2.25 0 0 1 22.5 5.25v9a2.25 2.25 0 0 1-2.25 2.25'],
        ['label' => 'Hukuki Talepler', 'url' => route('events.index'), 'active' => ['events.*'], 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V5.625A3.375 3.375 0 0 0 11.25 2.25H6.375A3.375 3.375 0 0 0 3 5.625v12.75a3.375 3.375 0 0 0 3.375 3.375h4.875m8.25-7.5h-4.875a3.375 3.375 0 0 0-3.375 3.375v.375m8.25-3.75v5.625A1.875 1.875 0 0 1 17.625 21.75H13.125A1.875 1.875 0 0 1 11.25 19.875v-2.25m8.25-3.375-4.875 4.875M14.625 8.25H11.25A3.375 3.375 0 0 1 7.875 4.875V2.25'],
    ];

    if ($isLegalUser) {
        $coreNavigation = [...$coreNavigation,
            ['label' => 'Hukuki Dosyalar', 'url' => route('case-files.index'), 'active' => ['case-files.*'], 'icon' => 'M3.75 9.75h16.5m-16.5 0A2.25 2.25 0 0 0 1.5 12v6.75A2.25 2.25 0 0 0 3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V12a2.25 2.25 0 0 0-2.25-2.25m-16.5 0V6.75A2.25 2.25 0 0 1 6 4.5h4.5l1.5 2.25h5.25A2.25 2.25 0 0 1 19.5 9v.75'],
            ['label' => 'Müvekkiller', 'url' => route('clients.index'), 'active' => ['clients.*'], 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z'],
            ['label' => 'Gelişmiş Arama', 'url' => route('search.index'), 'active' => ['search.*'], 'icon' => 'm21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z'],
        ];
    }

    $operationNavigation = $isLegalUser ? [
        ['label' => 'Evraklar', 'url' => route('legal-documents.index'), 'active' => ['legal-documents.*', 'document-versions.*'], 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V5.625A3.375 3.375 0 0 0 11.25 2.25H6.375A3.375 3.375 0 0 0 3 5.625v12.75a3.375 3.375 0 0 0 3.375 3.375h7.5A3.375 3.375 0 0 0 17.625 18v-1.5m-6.375-14.25V5.625A3.375 3.375 0 0 0 14.625 9h3.375'],
        ['label' => 'Hukuki Takvim', 'url' => route('legal-calendar.index'), 'active' => ['legal-calendar.*'], 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v12A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75v-12A2.25 2.25 0 0 1 5.25 4.5Z'],
        ['label' => 'Duruşmalar', 'url' => route('hearings.index'), 'active' => ['hearings.*'], 'icon' => 'M12 3v18m9-9H3m3.75-6.75L3 12l3.75 6.75m10.5-13.5L21 12l-3.75 6.75'],
        ['label' => 'Süreler', 'url' => route('deadlines.index'), 'active' => ['deadlines.*'], 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Görevler', 'url' => route('legal-tasks.index'), 'active' => ['legal-tasks.*'], 'icon' => 'm9 12.75 2.25 2.25L15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Tebligatlar', 'url' => route('service-notices.index'), 'active' => ['service-notices.*'], 'icon' => 'M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m4.178 0 5.478 2.949A2.25 2.25 0 0 0 22.5 16.34V6.91a2.25 2.25 0 0 0-1.183-1.981l-8.25-4.44a2.25 2.25 0 0 0-2.134 0l-8.25 4.44A2.25 2.25 0 0 0 1.5 6.91v9.43a2.25 2.25 0 0 0 2.933 1.984l5.478-2.949m4.178 0-1.022.55a2.25 2.25 0 0 1-2.134 0l-1.022-.55'],
        ['label' => 'Arabuluculuk', 'url' => route('mediations.index'), 'active' => ['mediations.*'], 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 9.094 9.094 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
        ['label' => 'Finans Hareketleri', 'url' => route('financial-entries.index'), 'active' => ['financial-entries.*'], 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.22 12.768 12 12 12c-.725 0-1.45-.22-2.036-.659-1.171-.879-1.171-2.303 0-3.182s3.071-.879 4.242 0L15 8.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Müvekkil İletişimleri', 'url' => route('client-communications.index'), 'active' => ['client-communications.*'], 'icon' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.142-4.03 7.5-9 7.5a10.22 10.22 0 0 1-4.38-.968L3 20.25l1.337-3.342C3.49 15.564 3 13.887 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z'],
        ['label' => 'Dosya Talepleri', 'url' => route('assignment-requests.index'), 'active' => ['assignment-requests.*'], 'icon' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 10.5h10.5a2.25 2.25 0 0 0 2.25-2.25v-6a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6A2.25 2.25 0 0 0 6.75 21Z'],
    ] : [];

    $managementNavigation = $user->isManager() ? [
        ['label' => 'Raporlar', 'url' => route('reports.index'), 'active' => ['reports.*'], 'icon' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25V21l3.75-2.25L15.75 21v-4.5H18a2.25 2.25 0 0 0 2.25-2.25V3M7.5 12l3-3 2.25 2.25L16.5 7.5'],
        ['label' => 'UYAP Aktarımı', 'url' => route('uyap-import.create'), 'active' => ['uyap-import.*'], 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-6L12 15m0 0 4.5-4.5M12 15V3'],
        ['label' => 'Kullanıcılar', 'url' => route('admin.users.index'), 'active' => ['admin.users.*'], 'icon' => 'M18 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6 9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm12.75 12a6.75 6.75 0 0 0-13.5 0m17.25 0a7.5 7.5 0 0 0-9.832-7.126'],
        ['label' => 'Olay Türleri', 'url' => route('admin.event-types.index'), 'active' => ['admin.event-types.*'], 'icon' => 'M9.568 3.057A9.77 9.77 0 0 1 12 2.75c1.152 0 2.257.2 3.283.568m3.66 2.25A9.728 9.728 0 0 1 21.25 12c0 1.152-.2 2.257-.568 3.283m-2.25 3.66A9.728 9.728 0 0 1 12 21.25c-1.152 0-2.257-.2-3.283-.568m-3.66-2.25A9.728 9.728 0 0 1 2.75 12c0-1.152.2-2.257.568-3.283M12 8.25v3.75l2.25 2.25'],
        ['label' => 'Dosya Türleri', 'url' => route('admin.case-types.index'), 'active' => ['admin.case-types.*'], 'icon' => 'M6.429 9.75 2.25 12l4.179 2.25m0-4.5L12 6.75l5.571 3m-11.142 0L12 12.75m5.571-3L21.75 12l-4.179 2.25m0-4.5L12 12.75m0 0v6.75m5.571-5.25L12 17.25l-5.571-3'],
        ['label' => 'Audit Kayıtları', 'url' => route('audit-logs.index'), 'active' => ['audit-logs.*'], 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ] : [];

    $navigationSections = [
        ['label' => 'Çalışma Alanı', 'items' => $coreNavigation],
        ['label' => 'Hukuk Operasyonu', 'items' => $operationNavigation],
        ['label' => 'Yönetim', 'items' => $managementNavigation],
    ];
@endphp

<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#293e47">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full antialiased">
    <div class="min-h-svh lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)]">
        <aside class="sticky top-0 hidden h-svh flex-col border-r border-slate-200 bg-white lg:flex" aria-label="Ana menü">
            <div class="flex h-20 items-center border-b border-slate-200 px-5">
                <a class="flex items-center gap-3" href="{{ route('dashboard') }}">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-900 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.339A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" /></svg>
                    </span>
                    <span>
                        <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-slate-400">Tepenet</span>
                        <span class="block font-serif text-lg font-semibold leading-tight text-slate-900">Avukat Portalı</span>
                    </span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-5">
                @foreach($navigationSections as $section)
                    @if($section['items'] !== [])
                        <section class="{{ $loop->first ? '' : 'mt-6' }}">
                            <h2 class="portal-section-label">{{ $section['label'] }}</h2>
                            <div class="mt-2 grid gap-1">
                                @foreach($section['items'] as $item)
                                    @php($active = request()->routeIs(...$item['active']))
                                    <a class="portal-nav-link {{ $active ? 'portal-nav-link-active' : '' }}" href="{{ $item['url'] }}" @if($active) aria-current="page" @endif>
                                        <svg class="portal-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-slate-200 p-3">
                <div class="rounded-lg bg-slate-50 p-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-800">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p><p class="truncate text-xs text-slate-500">{{ $user->role->name }}</p></div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-200 pt-3">
                        <a class="rounded-md px-2 py-1.5 text-center text-xs font-medium text-slate-600 hover:bg-white hover:text-slate-900" href="{{ route('security.show') }}">Güvenlik</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-md px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-white hover:text-slate-900">Çıkış</button></form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-40 border-b border-slate-200 bg-[#f4f3ef]/95 backdrop-blur-sm">
                <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:h-20 lg:px-8 xl:px-10">
                    <div class="flex min-w-0 items-center gap-3">
                        <a class="flex items-center gap-2 lg:hidden" href="{{ route('dashboard') }}">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-900 text-white"><svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.339A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" /></svg></span>
                            <span class="hidden font-serif font-semibold text-slate-900 min-[390px]:block">Avukat Portalı</span>
                        </a>
                        <div class="hidden min-w-0 lg:block">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Tepenet Hukuk Operasyonu</p>
                            <p class="truncate font-serif text-xl font-semibold text-slate-900">{{ $title ?? 'Çalışma Alanı' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($isLegalUser)
                            <a class="portal-icon-button hidden sm:inline-flex" href="{{ route('search.index') }}" title="Gelişmiş arama"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" /></svg></a>
                        @endif

                        <details class="relative">
                            <summary class="portal-icon-button cursor-pointer" aria-label="Bildirimleri aç">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                @if($navbarUnreadCount)<span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full border-2 border-[#f4f3ef] bg-red-600 px-1 text-[0.65rem] font-semibold leading-4 text-white">{{ $navbarUnreadCount }}</span>@endif
                            </summary>
                            <div class="absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3"><strong class="font-serif text-base text-slate-900">Bildirimler</strong><a class="text-xs font-semibold text-indigo-700" href="{{ route('notifications.index') }}">Tümünü gör</a></div>
                                <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                                    @forelse($navbarNotifications as $notification)
                                        <li class="{{ is_null($notification->read_at) ? 'bg-indigo-50/60' : '' }}"><a class="block px-4 py-3 hover:bg-slate-50" href="{{ route('notifications.open', $notification) }}"><span class="block text-sm {{ is_null($notification->read_at) ? 'font-semibold text-slate-900' : 'text-slate-600' }}">{{ $notification->data['message'] ?? 'Bildirim' }}</span><span class="mt-1 block text-xs text-slate-400">{{ $notification->created_at->format('d.m.Y H:i') }}</span></a></li>
                                    @empty
                                        <li class="px-4 py-10 text-center text-sm text-slate-500">Yeni bildirim bulunmuyor.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </details>

                        <details class="group lg:hidden">
                            <summary class="portal-icon-button cursor-pointer" aria-label="Menüyü aç">
                                <svg class="h-5 w-5 group-open:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                                <svg class="hidden h-5 w-5 group-open:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </summary>
                            <div class="fixed inset-x-0 bottom-0 top-16 overflow-y-auto border-t border-slate-200 bg-[#f4f3ef] px-4 py-5 sm:px-6">
                                <div class="mb-5 flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-800">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->role->name }}</p></div>
                                </div>
                                <nav class="grid gap-5">
                                    @foreach($navigationSections as $section)
                                        @if($section['items'] !== [])
                                            <section><h2 class="portal-section-label">{{ $section['label'] }}</h2><div class="mt-2 grid grid-cols-1 gap-1 min-[480px]:grid-cols-2">@foreach($section['items'] as $item)@php($active = request()->routeIs(...$item['active']))<a class="portal-nav-link {{ $active ? 'portal-nav-link-active' : '' }}" href="{{ $item['url'] }}"><svg class="portal-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg><span>{{ $item['label'] }}</span></a>@endforeach</div></section>
                                        @endif
                                    @endforeach
                                </nav>
                                <div class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-200 pt-5">
                                    <a class="rounded-lg border border-slate-300 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700" href="{{ route('security.show') }}">Hesap Güvenliği</a>
                                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Çıkış Yap</button></form>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[96rem] px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8 xl:px-10">
                @if(session('success'))
                    <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg><span>{{ session('success') }}</span></div>
                @endif
                @if($errors->any())
                    <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg><span>Lütfen işaretlenen alanları kontrol edin.</span></div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
