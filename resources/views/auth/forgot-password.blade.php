<x-layouts.guest
    title="Şifre Sıfırlama"
    eyebrow="Hesap kurtarma"
    heading="Şifrenizi yenileyin"
    description="Kayıtlı e-posta adresinize güvenli bir şifre sıfırlama bağlantısı göndereceğiz."
>
    @if(session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form class="grid gap-5" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700" for="email">Kurumsal e-posta</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
            @error('email')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button class="w-full rounded-lg bg-indigo-900 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-800" type="submit">Sıfırlama bağlantısı gönder</button>
    </form>

    <div class="mt-5 border-t border-slate-200 pt-5 text-center"><a class="text-sm font-semibold text-indigo-700 hover:text-indigo-900" href="{{ route('login') }}">Giriş sayfasına dön</a></div>
</x-layouts.guest>
