<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şifre Sıfırlama</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center p-4 sm:p-6">
    <main class="w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Şifre Sıfırlama</h1>
            <p class="mt-2 text-sm text-slate-600">E-posta adresinizi girin, sıfırlama bağlantısı gönderelim.</p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
            @if(session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                @error('email')
                    <p class="text-sm text-red-600 bg-red-50 rounded-md p-3">{{ $message }}</p>
                @enderror
                <button type="submit"
                    class="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Sıfırlama Bağlantısı Gönder
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    ← Giriş sayfasına dön
                </a>
            </div>
        </div>
    </main>
</body>
</html>
