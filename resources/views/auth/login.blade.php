<x-layouts.guest
    title="Giriş Yap"
    eyebrow="Kurumsal giriş"
    heading="Çalışma alanınıza giriş yapın"
    description="Rolünüze uygun giriş yöntemini seçerek güvenli oturumunuzu başlatın."
>
    <div class="grid grid-cols-2 rounded-lg bg-slate-100 p-1" role="tablist" aria-label="Giriş yöntemi">
        <button class="rounded-md bg-white px-3 py-2.5 text-sm font-semibold text-indigo-800 shadow-sm" id="tab-email" type="button" role="tab" aria-selected="true" onclick="switchTab('email')">Yönetici / Çalışan</button>
        <button class="rounded-md px-3 py-2.5 text-sm font-medium text-slate-500" id="tab-lawyer" type="button" role="tab" aria-selected="false" onclick="switchTab('lawyer')">Avukat</button>
    </div>

    <form class="mt-6 grid gap-5" method="POST" action="{{ route('login.store') }}">
        @csrf
        <input id="login_type" name="login_type" type="hidden" value="email">

        <div id="panel-email" role="tabpanel">
            <label class="block text-sm font-medium text-slate-700" for="email">Kurumsal e-posta</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="ad.soyad@kurum.com" autocomplete="username" autofocus>
        </div>

        <div class="hidden" id="panel-lawyer" role="tabpanel">
            <label class="block text-sm font-medium text-slate-700" for="sicil_no">Sicil numarası / T.C. kimlik numarası</label>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 font-mono tracking-wide focus:border-indigo-500 focus:ring-indigo-500" id="sicil_no" name="sicil_no" type="text" value="{{ old('sicil_no') }}" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" placeholder="11 haneli numara" autocomplete="username">
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <label class="block text-sm font-medium text-slate-700" for="password">Şifre</label>
                <a class="text-xs font-semibold text-indigo-700 hover:text-indigo-900" href="{{ route('password.request') }}">Şifremi unuttum</a>
            </div>
            <input class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-slate-600" for="remember">
            <input class="rounded border-slate-300 text-indigo-700 focus:ring-indigo-500" id="remember" name="remember" type="checkbox" value="1">
            Bu cihazda oturumu açık tut
        </label>

        @error('email')<p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
        @error('sicil_no')<p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror

        <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-900 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-800" type="submit">
            Güvenli giriş yap
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
        </button>
    </form>

    <script>
        function switchTab(tab) {
            const isEmail = tab === 'email';
            const emailTab = document.getElementById('tab-email');
            const lawyerTab = document.getElementById('tab-lawyer');
            const emailPanel = document.getElementById('panel-email');
            const lawyerPanel = document.getElementById('panel-lawyer');
            const emailInput = document.getElementById('email');
            const sicilInput = document.getElementById('sicil_no');

            emailTab.className = isEmail
                ? 'rounded-md bg-white px-3 py-2.5 text-sm font-semibold text-indigo-800 shadow-sm'
                : 'rounded-md px-3 py-2.5 text-sm font-medium text-slate-500';
            lawyerTab.className = !isEmail
                ? 'rounded-md bg-white px-3 py-2.5 text-sm font-semibold text-indigo-800 shadow-sm'
                : 'rounded-md px-3 py-2.5 text-sm font-medium text-slate-500';
            emailTab.setAttribute('aria-selected', isEmail ? 'true' : 'false');
            lawyerTab.setAttribute('aria-selected', isEmail ? 'false' : 'true');
            emailPanel.classList.toggle('hidden', !isEmail);
            lawyerPanel.classList.toggle('hidden', isEmail);
            emailInput.required = isEmail;
            sicilInput.required = !isEmail;
            document.getElementById('login_type').value = tab;
            (isEmail ? emailInput : sicilInput).focus();
        }

        @if(old('sicil_no'))
            document.addEventListener('DOMContentLoaded', () => switchTab('lawyer'));
        @endif
    </script>
</x-layouts.guest>
