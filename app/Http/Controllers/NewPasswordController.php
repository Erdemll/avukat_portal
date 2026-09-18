<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(string $token): View
    {
        return view('auth.reset-password', compact('token'));
    }

    public function store(ResetPasswordRequest $request, AuditService $audit): RedirectResponse
    {
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) use ($audit): void {
            if (! $user->is_active) {
                return;
            }
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            $audit->safelyLog(AuditAction::PasswordResetCompleted, $user, description: 'Parola sıfırlandı.');
        });

        return $status === Password::PasswordReset ? redirect()->route('login')->with('status', 'Parolanız sıfırlandı.') : back()->withErrors(['email' => __($status)]);
    }
}
