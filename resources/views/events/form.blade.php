<x-layouts.app :title="$event->exists ? 'Olayı Düzenle' : 'Yeni Olay Oluştur'">
    <a class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition hover:text-slate-900" href="{{ route('events.index') }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5" />
        </svg>
        Olaylar
    </a>

    <div class="mt-4 max-w-3xl">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h1 class="text-lg font-semibold text-slate-900">{{ $event->exists ? 'Olayı Düzenle' : 'Yeni Olay Oluştur' }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $event->exists ? 'Olay bilgilerini güncelleyin.' : 'Yeni bir hukuki olay kaydı oluşturun.' }}</p>
            </div>

            <form class="px-6 py-5" method="POST" action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}">
                @csrf
                @if($event->exists)
                    @method('PUT')
                @endif

                @if(!$event->exists)
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="event_type_id" class="block text-sm font-medium text-slate-700">Olay Türü <span class="text-red-500">*</span></label>
                            <select id="event_type_id" name="event_type_id" required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seçiniz</option>
                                @foreach($eventTypes as $eventType)
                                    <option value="{{ $eventType->id }}">{{ $eventType->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assigned_lawyer_id" class="block text-sm font-medium text-slate-700">Atanan Avukat <span class="text-red-500">*</span></label>
                            <select id="assigned_lawyer_id" name="assigned_lawyer_id" required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seçiniz</option>
                                @foreach($lawyers as $lawyer)
                                    <option value="{{ $lawyer->id }}">{{ $lawyer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="my-5 border-t border-slate-100"></div>
                @endif

                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Başlık <span class="text-red-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $event->title) }}" required placeholder="Olay başlığı"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="mt-5">
                    <label for="description" class="block text-sm font-medium text-slate-700">Açıklama <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="4" required placeholder="Olayın detaylı açıklaması"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $event->description) }}</textarea>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-slate-700">Öncelik <span class="text-red-500">*</span></label>
                        <select id="priority" name="priority" required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(App\EventPriority::cases() as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', $event->priority?->value) === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="occurred_at" class="block text-sm font-medium text-slate-700">Oluşma Tarihi</label>
                        <input id="occurred_at" name="occurred_at" type="datetime-local" value="{{ old('occurred_at', $event->occurred_at?->format('Y-m-d\TH:i')) }}"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-5">
                    <label for="current_process" class="block text-sm font-medium text-slate-700">Mevcut Süreç</label>
                    <textarea id="current_process" name="current_process" rows="3" placeholder="Olayın mevcut durumu hakkında bilgi"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('current_process', $event->current_process) }}</textarea>
                </div>

                <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ $event->exists ? 'Güncelle' : 'Oluştur' }}
                    </button>
                    <a class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('events.index') }}">
                        İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
