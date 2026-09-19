<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\VerifyTwoFactorCodeRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TwoFactorChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', ['maskedEmail' => $this->maskEmail($user->email)]);
    }

    public function store(VerifyTwoFactorCodeRequest $request, TwoFactorChallengeService $challenges, AuditService $audit): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            return redirect()->route('login');
        }

        $challenges->verify($user, $request->string('code')->toString());
        $remember = (bool) $request->session()->pull('two_factor_remember', false);
        $request->session()->forget('two_factor_user_id');
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->safelyLog(AuditAction::UserLogin, $user, description: 'İki aşamalı doğrulamayla başarılı giriş.');

        return redirect()->intended(route($user->isEmployee() ? 'events.index' : 'case-files.index'));
    }

    public function resend(Request $request, TwoFactorChallengeService $challenges): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            return redirect()->route('login');
        }

        $challenges->issue($user);

        return back()->with('success', 'Yeni doğrulama kodu gönderildi.');
    }

    private function pendingUser(Request $request): ?User
    {
        $user = User::query()->find($request->session()->get('two_factor_user_id'));

        return $user !== null && $user->is_active && $user->hasTwoFactorAuthenticationEnabled() ? $user : null;
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 2).str_repeat('*', max(2, mb_strlen($name) - 2)).'@'.$domain;
    }
}
