<x-layouts.guest
    title="Yeni Şifre Belirle"
    eyebrow="Hesap güvenliği"
    heading="Yeni şifrenizi belirleyin"
    description="Hesabınız için güçlü ve daha önce kullanmadığınız bir şifre oluşturun."
>
    <form class="grid gap-5" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input name="token" type="hidden" value="{{ $token }}">

        @error('token')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div>
            <label class="block text-sm font-medium text-slate-700" for="email">E-posta</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="username" autofocus required @readonly($email !== '')>
            @error('email')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="password">Yeni şifre</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" id="password" name="password" type="password" autocomplete="new-password" required>
            @error('password')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="password_confirmation">Yeni şifre tekrarı</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="w-full rounded-lg bg-indigo-900 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-800" type="submit">Şifreyi güvenli biçimde yenile</button>
    </form>
</x-layouts.guest>
