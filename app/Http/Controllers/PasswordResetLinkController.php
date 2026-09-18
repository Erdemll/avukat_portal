<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request, AuditService $audit): RedirectResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();
        if ($user?->is_active) {
            Password::sendResetLink(['email' => $user->email]);
            $audit->safelyLog(AuditAction::PasswordResetRequested, $user, description: 'Parola sıfırlama talep edildi.');
        }

        return back()->with('status', 'Parola sıfırlama bağlantısı gönderildiyse e-posta adresinize ulaşacaktır.');
    }
}
