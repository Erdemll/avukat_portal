<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\UpdateTwoFactorAuthenticationRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function show(Request $request): View
    {
        return view('security.show', ['user' => $request->user()]);
    }

    public function enable(UpdateTwoFactorAuthenticationRequest $request, AuditService $audit): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['two_factor_enabled_at' => now()])->save();
        $audit->log(AuditAction::TwoFactorEnabled, $user, auditable: $user, description: 'İki aşamalı doğrulama etkinleştirildi.');

        return back()->with('success', 'İki aşamalı doğrulama etkinleştirildi. Sonraki girişte e-posta kodu istenecek.');
    }

    public function disable(UpdateTwoFactorAuthenticationRequest $request, AuditService $audit): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['two_factor_enabled_at' => null])->save();
        $user->twoFactorChallenges()->whereNull('consumed_at')->update(['consumed_at' => now()]);
        $audit->log(AuditAction::TwoFactorDisabled, $user, auditable: $user, description: 'İki aşamalı doğrulama devre dışı bırakıldı.');

        return back()->with('success', 'İki aşamalı doğrulama devre dışı bırakıldı.');
    }
}
