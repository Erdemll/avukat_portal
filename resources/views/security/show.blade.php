<x-layouts.app title="Hesap Güvenliği">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Hesabım</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Güvenlik ayarları</h1>
            <p class="mt-2 text-sm text-slate-600">Şifrenize ek olarak e-posta adresinize gönderilen süreli kodla hesabınızı koruyun.</p>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-900">İki aşamalı doğrulama</h2>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->hasTwoFactorAuthenticationEnabled() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $user->hasTwoFactorAuthenticationEnabled() ? 'Etkin' : 'Kapalı' }}
                        </span>
                    </div>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">Giriş kodları <strong>{{ $user->email }}</strong> adresine gönderilir. E-posta hesabınızın da güçlü bir parolayla korunması önerilir.</p>
                </div>
            </div>

            <form class="mt-6 max-w-md space-y-4" method="POST" action="{{ $user->hasTwoFactorAuthenticationEnabled() ? route('security.two-factor.disable') : route('security.two-factor.enable') }}">
                @csrf
                @if($user->hasTwoFactorAuthenticationEnabled()) @method('DELETE') @endif
                <div>
                    <label class="block text-sm font-medium text-slate-700" for="current_password">Mevcut şifreniz</label>
                    <input class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button class="rounded-lg px-4 py-2.5 text-sm font-semibold text-white {{ $user->hasTwoFactorAuthenticationEnabled() ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-700 hover:bg-indigo-800' }}">
                    {{ $user->hasTwoFactorAuthenticationEnabled() ? 'İki aşamalı doğrulamayı kapat' : 'İki aşamalı doğrulamayı etkinleştir' }}
                </button>
            </form>
        </section>
    </div>
</x-layouts.app>
