<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TwoFactorChallengeService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private TwoFactorChallengeService $twoFactorChallenges) {}

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
        $limiter = app(RateLimiter::class);
        $key = 'lawyer-login:'.$sicilNo;

        if ($limiter->tooManyAttempts($key, 5)) {
            return back()->withErrors(['sicil_no' => 'Çok fazla başarısız deneme. Lütfen daha sonra tekrar deneyin.'])->onlyInput('sicil_no');
        }

        $genericError = back()->withErrors(['sicil_no' => 'Sicil numarası veya şifre hatalı.'])->onlyInput('sicil_no');
        $user = User::query()
            ->where('tc_kimlik_no', $sicilNo->toString())
            ->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))
            ->first();

        if ($user === null || ! $user->is_active || ! Auth::attempt([
            'id' => $user->id,
            'password' => $request->string('password')->toString(),
        ], $request->boolean('remember'))) {
            $limiter->hit($key, 60);

            if ($user !== null && ! $user->is_active) {
                $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Pasif hesapla giriş denemesi (avukat): '.substr($sicilNo, 0, 3).'***');
            } else {
                $audit->safelyLog(AuditAction::UserLoginFailed, null, description: 'Başarısız giriş denemesi (avukat): '.substr($sicilNo, 0, 3).'***');
            }

            return $genericError;
        }

        $limiter->clear($key);
        if ($request->user()->hasTwoFactorAuthenticationEnabled()) {
            return $this->beginTwoFactorChallenge($request);
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $audit->safelyLog(AuditAction::UserLogin, $request->user(), description: 'Başarılı giriş (avukat).');

        return redirect()->intended(route('case-files.index'));
    }

    private function handleEmailLogin(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $user = User::query()->where('email', $credentials['email'])->first();

        $genericError = back()->withErrors(['email' => 'E-posta adresi veya şifre hatalı.'])->onlyInput('email');

        if ($user === null) {
            $audit->safelyLog(AuditAction::UserLoginFailed, null, description: 'Başarısız giriş denemesi: '.Str::mask($credentials['email'], '*', 3));

            return $genericError;
        }

        if (! $user->is_active) {
            $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Pasif hesapla giriş denemesi: '.Str::mask($credentials['email'], '*', 3));

            return $genericError;
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $audit->safelyLog(AuditAction::UserLoginFailed, $user, description: 'Başarısız giriş denemesi: '.Str::mask($credentials['email'], '*', 3));

            return $genericError;
        }

        if ($request->user()->hasTwoFactorAuthenticationEnabled()) {
            return $this->beginTwoFactorChallenge($request);
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $audit->safelyLog(AuditAction::UserLogin, $request->user(), description: 'Başarılı giriş.');

        return redirect()->intended(route($request->user()->isEmployee() ? 'events.index' : 'case-files.index'));
    }

    private function beginTwoFactorChallenge(LoginRequest $request): RedirectResponse
    {
        $user = $request->user();
        Auth::logoutCurrentDevice();
        $request->session()->regenerate();
        $request->session()->put([
            'two_factor_user_id' => $user->id,
            'two_factor_remember' => $request->boolean('remember'),
        ]);
        $this->twoFactorChallenges->issue($user);

        return redirect()->route('two-factor.challenge');
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
