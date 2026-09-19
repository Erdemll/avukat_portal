<div>
    <!-- Do what you can, with what you have, where you are. - Theodore Roosevelt -->
</div>
@php
    $isEditing = $client->exists;
    $party = $client->party;
    $identifier = $party?->relationLoaded('identifiers') ? $party->identifiers->first() : null;
@endphp
<x-layouts.app :title="$isEditing ? 'Müvekkil Düzenle' : 'Yeni Müvekkil'">
    <a class="text-sm font-medium text-slate-500 hover:text-slate-900" href="{{ route('clients.index') }}">&larr; Müvekkiller</a>
    <div class="mt-4 max-w-4xl rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5"><h1 class="text-xl font-bold text-slate-950">{{ $isEditing ? 'Müvekkili Düzenle' : 'Yeni Müvekkil Oluştur' }}</h1><p class="mt-1 text-sm text-slate-500">Kimlik ve iletişim bilgilerini eksiksiz kaydedin.</p></div>
        <form class="grid gap-5 px-6 py-6 md:grid-cols-2" method="POST" action="{{ $isEditing ? route('clients.update', $client) : route('clients.store') }}">@csrf @if($isEditing) @method('PUT') <input type="hidden" name="lock_version" value="{{ $client->lock_version }}"> @endif
            <div><label class="text-sm font-medium text-slate-700">Kişi türü</label><select name="type" required class="mt-1 block w-full rounded-lg border-slate-300">@foreach(App\PartyType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type', $party?->type?->value ?? 'individual') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
            @if($isEditing)<div><label class="text-sm font-medium text-slate-700">Durum</label><select name="status" class="mt-1 block w-full rounded-lg border-slate-300"><option value="active" @selected(old('status', $client->status) === 'active')>Aktif</option><option value="inactive" @selected(old('status', $client->status) === 'inactive')>Pasif</option></select></div>@endif
            <div><label class="text-sm font-medium text-slate-700">Ad</label><input name="name" value="{{ old('name', $party?->name) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
            <div><label class="text-sm font-medium text-slate-700">Soyad</label><input name="surname" value="{{ old('surname', $party?->surname) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
            <div class="md:col-span-2"><label class="text-sm font-medium text-slate-700">Şirket unvanı</label><input name="company_name" value="{{ old('company_name', $party?->company_name) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
            <div><label class="text-sm font-medium text-slate-700">Telefon</label><input name="phone" value="{{ old('phone', $party?->phone) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
            <div><label class="text-sm font-medium text-slate-700">E-posta</label><input type="email" name="email" value="{{ old('email', $party?->email) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
            <div class="md:col-span-2"><label class="text-sm font-medium text-slate-700">Adres</label><textarea name="address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('address', $party?->address) }}</textarea></div>
            @if(auth()->user()->isManager())
                <div><label class="text-sm font-medium text-slate-700">Kimlik türü</label><select name="identifier_type" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">Belirtilmedi</option>@foreach(App\PartyIdentifierType::cases() as $type)<option value="{{ $type->value }}" @selected(old('identifier_type', $identifier?->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
                <div><label class="text-sm font-medium text-slate-700">Kimlik / vergi numarası</label><input name="identifier_value" value="" autocomplete="off" placeholder="{{ $identifier ? 'Değiştirmek için yeni değer girin' : '' }}" class="mt-1 block w-full rounded-lg border-slate-300"><p class="mt-1 text-xs text-slate-500">Bu bilgi şifreli saklanır.</p></div>
            @endif
            <div><label class="text-sm font-medium text-slate-700">Müvekkillik başlangıcı</label><input type="date" name="client_since" value="{{ old('client_since', $client->client_since?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300"></div>
            <div class="md:col-span-2"><label class="text-sm font-medium text-slate-700">İletişim notları</label><textarea name="party_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('party_notes', $party?->notes) }}</textarea></div>
            <div class="md:col-span-2"><label class="text-sm font-medium text-slate-700">Müvekkil notları</label><textarea name="client_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('client_notes', $client->notes) }}</textarea></div>
            <div class="flex gap-3 border-t border-slate-100 pt-5 md:col-span-2"><button class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white">Kaydet</button><a class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700" href="{{ route('clients.index') }}">İptal</a></div>
        </form>
    </div>
</x-layouts.app>
