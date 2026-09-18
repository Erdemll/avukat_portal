<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş Yap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 to-slate-200 flex items-center justify-center p-4 sm:p-6">
    <main class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-700 text-white mb-4">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Hukuki Olay Yönetimi</h1>
            <p class="mt-2 text-sm text-slate-600">Hesabınıza giriş yapın</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            {{-- Tabs --}}
            <div class="flex border-b border-slate-200">
                <button type="button"
                    id="tab-email"
                    class="flex-1 px-4 py-3 text-sm font-semibold text-indigo-700 border-b-2 border-indigo-700 bg-indigo-50 transition"
                    onclick="switchTab('email')">
                    <svg class="inline-block h-4 w-4 mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    Yönetici / Çalışan
                </button>
                <button type="button"
                    id="tab-lawyer"
                    class="flex-1 px-4 py-3 text-sm font-semibold text-slate-500 border-b-2 border-transparent hover:text-slate-700 transition"
                    onclick="switchTab('lawyer')">
                    <svg class="inline-block h-4 w-4 mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" />
                    </svg>
                    Avukat
                </button>
            </div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    {{-- Email Login Tab --}}
                    <div id="panel-email">
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" autofocus
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="ornek@hukuk.com">
                        </div>
                    </div>

                    {{-- Lawyer Login Tab --}}
                    <div id="panel-lawyer" class="hidden">
                        <div>
                            <label for="sicil_no" class="block text-sm font-medium text-slate-700">Sicil No (TC Kimlik No)</label>
                            <input id="sicil_no" type="text" name="sicil_no" value="{{ old('sicil_no') }}" maxlength="11" inputmode="numeric"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                pattern="[0-9]{11}" placeholder="12345678901">
                        </div>
                    </div>

                    {{-- Password (shared) --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Şifre</label>
                        <input id="password" type="password" name="password" required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox" value="1"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="remember" class="ml-2 text-sm text-slate-600">Beni hatırla</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                            Şifremi unuttum
                        </a>
                    </div>

                    @error('email')
                        <p class="text-sm text-red-600 bg-red-50 rounded-lg p-3">{{ $message }}</p>
                    @enderror
                    @error('sicil_no')
                        <p class="text-sm text-red-600 bg-red-50 rounded-lg p-3">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                        class="w-full rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                        Giriş Yap
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tab) {
            const emailTab = document.getElementById('tab-email');
            const lawyerTab = document.getElementById('tab-lawyer');
            const emailPanel = document.getElementById('panel-email');
            const lawyerPanel = document.getElementById('panel-lawyer');
            const emailInput = document.getElementById('email');
            const sicilInput = document.getElementById('sicil_no');

            if (tab === 'email') {
                emailTab.className = 'flex-1 px-4 py-3 text-sm font-semibold text-indigo-700 border-b-2 border-indigo-700 bg-indigo-50 transition';
                lawyerTab.className = 'flex-1 px-4 py-3 text-sm font-semibold text-slate-500 border-b-2 border-transparent hover:text-slate-700 transition';
                emailPanel.classList.remove('hidden');
                lawyerPanel.classList.add('hidden');
                emailInput.required = true;
                sicilInput.required = false;
                sicilInput.value = '';
                emailInput.focus();
            } else {
                lawyerTab.className = 'flex-1 px-4 py-3 text-sm font-semibold text-indigo-700 border-b-2 border-indigo-700 bg-indigo-50 transition';
                emailTab.className = 'flex-1 px-4 py-3 text-sm font-semibold text-slate-500 border-b-2 border-transparent hover:text-slate-700 transition';
                lawyerPanel.classList.remove('hidden');
                emailPanel.classList.add('hidden');
                sicilInput.required = true;
                emailInput.required = false;
                emailInput.value = '';
                sicilInput.focus();
            }
        }

        @if(old('sicil_no'))
            document.addEventListener('DOMContentLoaded', () => switchTab('lawyer'));
        @endif
    </script>
</body>
</html>
