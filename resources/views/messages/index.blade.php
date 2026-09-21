<x-layouts.app title="Mesajlar">
    <section
        class="portal-panel grid min-h-[36rem] w-full min-w-0 max-w-none overflow-hidden rounded-2xl lg:h-[calc(100svh-9rem)] lg:grid-cols-[minmax(15rem,18rem)_minmax(0,1fr)] xl:grid-cols-[19rem_minmax(0,1fr)]"
        data-messages-root
        data-current-user-id="{{ $currentUserId }}"
        data-conversation-store-url="{{ route('messages.conversations.store') }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <aside class="flex min-h-[36rem] min-w-0 flex-col border-slate-200 lg:min-h-0 lg:border-r" data-messages-sidebar>
            <div class="border-b border-slate-200 px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-indigo-700">Özel iletişim</p>
                        <h1 class="mt-1 text-2xl font-semibold text-slate-950">Avukatlar</h1>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-800" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.142-4.03 7.5-9 7.5a10.22 10.22 0 0 1-4.38-.968L3 20.25l1.337-3.342C3.49 15.564 3 13.887 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" /></svg>
                    </span>
                </div>

                <label class="relative mt-4 block">
                    <span class="sr-only">Avukat ara</span>
                    <svg class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" /></svg>
                    <input class="w-full rounded-xl border py-2.5 pl-10 pr-3 text-sm" type="search" placeholder="Avukat ara..." autocomplete="off" data-lawyer-search>
                </label>
            </div>

            <div class="flex-1 overflow-y-auto p-2" data-lawyer-list>
                @forelse($lawyers as $lawyer)
                    @php
                        $initials = collect(preg_split('/\s+/', trim($lawyer['name'])))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    <a
                        class="group flex w-full touch-manipulation items-center gap-3 rounded-xl px-3 py-3 text-left hover:bg-slate-50 focus-visible:bg-slate-50"
                        href="{{ route('messages.index', $lawyer['conversation_id'] ? ['conversation' => $lawyer['conversation_id']] : ['lawyer' => $lawyer['id']]) }}"
                        data-lawyer-row
                        data-lawyer-id="{{ $lawyer['id'] }}"
                        data-lawyer-name="{{ $lawyer['name'] }}"
                        data-lawyer-email="{{ $lawyer['email'] }}"
                        data-conversation-id="{{ $lawyer['conversation_id'] }}"
                    >
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800" aria-hidden="true">{{ $initials }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $lawyer['name'] }}</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500" data-lawyer-preview>{{ $lawyer['last_message'] ? \Illuminate\Support\Str::limit($lawyer['last_message'], 48) : 'Sohbet başlat' }}</span>
                        </span>
                        <span class="flex shrink-0 flex-col items-end gap-1">
                            <time class="text-[0.68rem] text-slate-400" data-lawyer-time datetime="{{ $lawyer['last_message_at'] }}"></time>
                            <span class="{{ $lawyer['unread_count'] > 0 ? 'inline-flex' : 'hidden' }} min-w-5 items-center justify-center rounded-full bg-indigo-800 px-1.5 py-0.5 text-[0.68rem] font-bold text-white" data-unread-count aria-label="{{ $lawyer['unread_count'] }} okunmamış mesaj">{{ $lawyer['unread_count'] }}</span>
                        </span>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-serif text-lg font-semibold text-slate-800">Aktif avukat bulunmuyor</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Mesajlaşabileceğiniz aktif avukatlar burada görünecek.</p>
                    </div>
                @endforelse
                <p class="hidden px-5 py-10 text-center text-sm text-slate-500" data-search-empty>Aramanızla eşleşen avukat bulunamadı.</p>
            </div>
        </aside>

        <div class="hidden min-h-[36rem] min-w-0 flex-col bg-slate-50/60 lg:flex lg:min-h-0" data-chat-panel>
            <div class="flex flex-1 items-center justify-center p-8" data-chat-placeholder>
                <div class="max-w-sm text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-indigo-100 bg-white text-indigo-700 shadow-sm" aria-hidden="true">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3h6m-9.75 8.25 1.718-3.437A9 9 0 1 1 21 12a9 9 0 0 1-15.532 6.187L3.75 19.5Z" /></svg>
                    </span>
                    <h2 class="mt-5 text-2xl font-semibold text-slate-900">Güvenli mesajlaşma</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Mesajlaşmak için bir avukat seçiniz.</p>
                </div>
            </div>

            <div class="hidden min-h-0 flex-1 flex-col" data-chat-content>
                <header class="z-10 flex min-h-18 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-5">
                    <button class="portal-icon-button lg:hidden" type="button" aria-label="Avukat listesine dön" data-chat-back>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </button>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800" data-chat-initials aria-hidden="true"></span>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-slate-950" data-chat-name></h2>
                        <p class="text-xs text-slate-500" data-chat-status>Özel görüşme</p>
                    </div>
                </header>

                <div class="relative min-h-0 flex-1">
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-50/90" data-chat-loading role="status">
                        <div class="text-center text-sm font-medium text-slate-600">
                            <span class="mx-auto mb-3 block h-7 w-7 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-700"></span>
                            Görüşme yükleniyor…
                        </div>
                    </div>
                    <div class="h-full overflow-y-auto px-4 py-5 sm:px-6" data-message-scroll>
                        <div class="mb-4 hidden justify-center" data-older-wrapper>
                            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60" type="button" data-load-older>Daha eski mesajları yükle</button>
                        </div>
                        <div class="grid gap-3" data-message-list aria-live="polite"></div>
                        <div class="hidden py-16 text-center" data-message-empty>
                            <p class="font-serif text-xl font-semibold text-slate-800">Henüz mesaj bulunmuyor.</p>
                            <p class="mt-2 text-sm text-slate-500">İlk mesajı siz gönderin.</p>
                        </div>
                    </div>
                </div>

                <form class="border-t border-slate-200 bg-white p-3 sm:p-4" data-message-form>
                    <label class="sr-only" for="message-body">Mesajınız</label>
                    <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100">
                        <textarea id="message-body" class="max-h-36 min-h-11 flex-1 resize-none border-0 bg-transparent px-2 py-2 text-sm shadow-none focus:ring-0" name="body" rows="1" maxlength="5000" placeholder="Mesaj yaz..." aria-label="Mesajınız" data-message-input></textarea>
                        <button class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-900 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800 disabled:cursor-not-allowed disabled:opacity-60" type="submit" data-message-submit>
                            <span data-submit-label>Gönder</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 12 3-3m-3 3 3 3m-3-3h12M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 17.25V6.75A2.25 2.25 0 0 1 5.25 4.5Z" /></svg>
                        </button>
                    </div>
                    <p class="mt-2 min-h-5 text-xs text-red-700" data-message-error role="alert"></p>
                </form>
            </div>
        </div>
    </section>

    <script nonce="{{ Vite::cspNonce() }}" type="application/json" data-messages-config>@json(['lawyers' => $lawyers, 'initial_conversation_id' => $initialConversationId, 'initial_lawyer_id' => $initialLawyerId])</script>
    @vite('resources/js/messages.js')
</x-layouts.app>
