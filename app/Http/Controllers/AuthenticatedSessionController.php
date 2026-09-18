<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\LoginRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        if ($request->isLawyerLogin()) {
            return $this->handleLawyerLogin($request, $audit);
        }

        return $this->handleEmailLogin($request, $audit);
    }

    private function handleLawyerLogin(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $sicilNo = $request->string('sicil_no');
        $lawyerRoleId = Role::query()->where('slug', 'lawyer')->value('id');
        $user = User::query()
            ->where('tc_kimlik_no', $sicilNo)
            ->where('role_id', $lawyerRoleId)
            ->first();

        if ($user === null) {
            return back()->withErrors(['sicil_no' => 'Sicil numarası ile eşleşen avukat bulunamadı.'])->onlyInput('sicil_no');
        }

        if (! $user->is_active) {
            $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Pasif hesapla giriş denemesi (avukat): '.$sicilNo);

            return back()->withErrors(['sicil_no' => 'Hesabınız pasif durumdadır.'])->onlyInput('sicil_no');
        }

        if (! Hash::check($request->string('password'), $user->password)) {
            $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Başarısız giriş denemesi (avukat): '.$sicilNo);

            return back()->withErrors(['sicil_no' => 'Sicil numarası veya şifre hatalı.'])->onlyInput('sicil_no');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->safelyLog(AuditAction::UserLogin, $user, description: 'Başarılı giriş (avukat).');

        return redirect()->intended(route('events.index'));
    }

    private function handleEmailLogin(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user !== null && ! $user->is_active && Hash::check($credentials['password'], $user->password)) {
            $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Pasif hesapla giriş denemesi: '.$credentials['email']);

            return back()->withErrors(['email' => 'Hesabınız pasif durumdadır.'])->onlyInput('email');
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Başarısız giriş denemesi: '.$credentials['email']);

            return back()->withErrors(['email' => 'E-posta adresi veya şifre hatalı.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $audit->safelyLog(AuditAction::UserLogin, $request->user(), description: 'Başarılı giriş.');

        return redirect()->intended(route('events.index'));
    }

    public function destroy(Request $request, AuditService $audit): RedirectResponse
    {
        $audit->safelyLog(AuditAction::UserLogout, $request->user(), description: 'Çıkış yapıldı.');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
