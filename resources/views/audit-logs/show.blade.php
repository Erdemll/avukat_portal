<x-layouts.app title="Audit Kaydı Detayı">
    <a class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition hover:text-slate-900" href="{{ route('audit-logs.index') }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5" />
        </svg>
        Audit Kayıtları
    </a>

    <div class="mt-4 max-w-3xl">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-5 sm:px-6">
                <span class="font-mono text-sm font-bold text-indigo-600">{{ $auditLog->action->value }}</span>
                <p class="mt-2 break-words text-sm text-slate-700">{{ $auditLog->description }}</p>
            </div>

            <div class="px-4 py-5 sm:px-6">
                <dl class="grid gap-4 rounded-lg bg-slate-50 p-4 text-sm">
                    <div class="grid gap-1 sm:grid-cols-3 sm:gap-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Tarih</dt>
                        <dd class="text-slate-900 sm:col-span-2">{{ $auditLog->created_at->format('d.m.Y H:i:s') }}</dd>
                    </div>
                    <div class="grid gap-1 sm:grid-cols-3 sm:gap-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Kullanıcı</dt>
                        <dd class="break-words font-medium text-slate-900 sm:col-span-2">{{ $auditLog->user?->name ?: '-' }}</dd>
                    </div>
                    <div class="grid gap-1 sm:grid-cols-3 sm:gap-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">IP Adresi</dt>
                        <dd class="break-all font-mono text-sm text-slate-700 sm:col-span-2">{{ $auditLog->ip_address ?: '-' }}</dd>
                    </div>
                    <div class="grid gap-1 sm:grid-cols-3 sm:gap-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">User Agent</dt>
                        <dd class="break-all text-xs text-slate-600 sm:col-span-2">{{ $auditLog->user_agent ?: '-' }}</dd>
                    </div>
                    @if($auditLog->event)
                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">İlgili Olay</dt>
                            <dd class="min-w-0 sm:col-span-2">
                                <a class="break-words font-medium text-indigo-600 hover:underline" href="{{ route('events.show', $auditLog->event) }}">
                                    {{ $auditLog->event->event_no }} · {{ $auditLog->event->title }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($auditLog->auditable)
                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Model</dt>
                            <dd class="break-all font-mono text-sm text-slate-700 sm:col-span-2">{{ class_basename($auditLog->auditable) }} #{{ $auditLog->auditable_id }}</dd>
                        </div>
                    @endif
                </dl>

                @if($auditLog->old_values)
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-slate-900">Eski Değerler</h2>
                        <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-100 p-4 text-sm">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif

                @if($auditLog->new_values)
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-slate-900">Yeni Değerler</h2>
                        <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-100 p-4 text-sm">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
