<!DOCTYPE html>
<html lang="tr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800">
    <div class="min-h-full">
        {{-- Header --}}
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:px-6 lg:px-8" aria-label="Ana menü">
                <div class="flex items-center gap-4">
                    <a class="flex items-center gap-2 text-lg font-bold text-slate-900" href="{{ route('dashboard') }}">
                        <svg class="h-7 w-7 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.339A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75c.69 0 1.373.036 2.046.107" />
                        </svg>
                        <span class="hidden sm:inline">Hukuk Portalı</span>
                    </a>
                </div>

                <div class="hidden items-center gap-1 lg:flex">
                    <a class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                    <a class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 {{ request()->routeIs('events.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('events.index') }}">
                        Olaylar
                    </a>

                    @if(auth()->user()->isManager())
                        <a class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('admin.users.index') }}">
                            Kullanıcılar
                        </a>
                        <a class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 {{ request()->routeIs('admin.event-types.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('admin.event-types.index') }}">
                            Olay Türleri
                        </a>
                        <a class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 {{ request()->routeIs('audit-logs.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('audit-logs.index') }}">
                            Audit Logları
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    {{-- Notifications --}}
                    <details class="relative">
                        <summary class="cursor-pointer rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                @if($navbarUnreadCount)
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-semibold text-white">{{ $navbarUnreadCount }}</span>
                                @endif
                            </span>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 w-80 rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <strong class="text-sm text-slate-900">Bildirimler</strong>
                                <a class="text-xs font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('notifications.index') }}">Tümünü Gör</a>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                @forelse($navbarNotifications as $notification)
                                    <li class="px-4 py-3 {{ is_null($notification->read_at) ? 'bg-indigo-50/50' : '' }}">
                                        <a class="block text-sm {{ is_null($notification->read_at) ? 'font-semibold text-slate-900' : 'text-slate-600' }}" href="{{ isset($notification->data['event_id']) ? route('events.show', $notification->data['event_id']) : route('notifications.index') }}">
                                            {{ $notification->data['message'] ?? 'Bildirim' }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">{{ $notification->created_at->format('d.m.Y H:i') }}</div>
                                    </li>
                                @empty
                                    <li class="px-4 py-6 text-center text-sm text-slate-500">Bildirim bulunmuyor.</li>
                                @endforelse
                            </ul>
                        </div>
                    </details>

                    {{-- User menu --}}
                    <div class="ml-1 flex items-center gap-2 border-l border-slate-200 pl-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->role->name }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" title="Çıkış yap">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </nav>

            {{-- Mobile nav --}}
            <div class="border-t border-slate-100 lg:hidden">
                <div class="flex gap-1 overflow-x-auto px-4 py-2">
                    <a class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 {{ request()->routeIs('events.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('events.index') }}">Olaylar</a>
                    @if(auth()->user()->isManager())
                        <a class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('admin.users.index') }}">Kullanıcılar</a>
                        <a class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 {{ request()->routeIs('admin.event-types.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('admin.event-types.index') }}">Olay Türleri</a>
                        <a class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 {{ request()->routeIs('audit-logs.*') ? 'bg-indigo-50 text-indigo-700' : '' }}" href="{{ route('audit-logs.index') }}">Audit</a>
                    @endif
                </div>
            </div>
        </header>

        {{-- Main content --}}
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-5 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    <svg class="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-5 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    Lütfen form alanlarını kontrol edin.
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
