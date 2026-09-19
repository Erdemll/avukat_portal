<x-layouts.guest
    title="Giriş Doğrulama"
    eyebrow="İki aşamalı doğrulama"
    heading="Girişinizi doğrulayın"
    description="{{ $maskedEmail }} adresine gönderilen 6 haneli kodu girin. Kod 10 dakika boyunca geçerlidir."
>
    @if(session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form class="grid gap-5" method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700" for="code">Doğrulama kodu</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 text-center font-mono text-2xl tracking-[0.35em] focus:border-indigo-500 focus:ring-indigo-500" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus required>
            @error('code')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button class="w-full rounded-lg bg-indigo-900 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-800">Doğrula ve giriş yap</button>
    </form>

    <form class="mt-3" method="POST" action="{{ route('two-factor.resend') }}">
        @csrf
        <button class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Yeni kod gönder</button>
    </form>
</x-layouts.guest>
