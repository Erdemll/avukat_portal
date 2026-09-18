<x-layouts.app :title="$user->exists ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı'">
    <a class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition hover:text-slate-900" href="{{ route('admin.users.index') }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5" />
        </svg>
        Kullanıcılar
    </a>

    <div class="mt-4 max-w-2xl">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h1 class="text-lg font-semibold text-slate-900">{{ $user->exists ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı Oluştur' }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $user->exists ? 'Kullanıcı bilgilerini güncelleyin.' : 'Sisteme yeni bir kullanıcı ekleyin.' }}</p>
            </div>

            <form class="px-6 py-5" method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                @csrf
                @if($user->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Ad Soyad <span class="text-red-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">E-posta <span class="text-red-500">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div id="tc_kimlik_no_field" class="mt-5 hidden">
                    <label for="tc_kimlik_no" class="block text-sm font-medium text-slate-700">TC Kimlik No <span class="text-red-500">*</span></label>
                    <input id="tc_kimlik_no" name="tc_kimlik_no" value="{{ old('tc_kimlik_no', $user->tc_kimlik_no) }}" maxlength="11" inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        pattern="[0-9]{11}" placeholder="12345678901">
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">Telefon</label>
                        <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+90 5xx xxx xx xx"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="role_id" class="block text-sm font-medium text-slate-700">Rol <span class="text-red-500">*</span></label>
                        <select id="role_id" name="role_id" required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seçiniz</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(!$user->exists)
                    <div class="mt-5 flex items-center rounded-lg bg-slate-50 p-3">
                        <input id="is_active" name="is_active" type="checkbox" value="1" checked
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="ml-2 text-sm text-slate-700">Hesabı aktif oluştur</label>
                    </div>
                @endif

                <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Kaydet
                    </button>
                    <a class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" href="{{ route('admin.users.index') }}">
                        İptal
                    </a>
                </div>
            </form>
        </div>

        @if($user->exists)
            <div class="mt-4 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Hesap İşlemleri</h2>
                </div>
                <div class="px-6 py-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ $user->is_active ? route('admin.users.deactivate', $user) : route('admin.users.activate', $user) }}">
                        @csrf
                        <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-slate-50 {{ $user->is_active ? 'text-red-700 hover:border-red-300' : 'text-emerald-700 hover:border-emerald-300' }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            {{ $user->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                        </button>
                    </form>
                    @if($user->is_active)
                        <form method="POST" action="{{ route('admin.users.send-password-reset', $user) }}">
                            @csrf
                            <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Şifre Sıfırlama Gönder
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @php
        $lawyerRoleId = $roles->firstWhere('slug', 'lawyer')?->id;
        $isLawyer = $user->role_id === $lawyerRoleId;
        $selectedRoleId = old('role_id', $user->role_id);
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tcField = document.getElementById('tc_kimlik_no_field');
            const tcInput = document.getElementById('tc_kimlik_no');
            const roleSelect = document.getElementById('role_id');
            const lawyerRoleId = {{ $lawyerRoleId ?? 'null' }};

            function toggleTcField() {
                const isLawyer = roleSelect.value == lawyerRoleId;
                tcField.classList.toggle('hidden', !isLawyer);
                tcInput.required = isLawyer;
                if (!isLawyer) {
                    tcInput.value = '';
                }
            }

            roleSelect.addEventListener('change', toggleTcField);

            // Initial state
            const isInitiallyLawyer = roleSelect.value == lawyerRoleId || @js($isLawyer);
            if (isInitiallyLawyer) {
                tcField.classList.remove('hidden');
                tcInput.required = true;
            }
        });
    </script>
</x-layouts.app>
