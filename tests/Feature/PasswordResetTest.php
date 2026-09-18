<?php

use App\AuditAction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('guests can view the password reset request page', function () {
    $this->get(route('password.request'))->assertOk();
});

test('managers can resend password setup mail but employees cannot', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $target = userWithRole('employee');
    $this->actingAs($manager)->post(route('admin.users.send-password-reset', $target))->assertRedirect();
    $this->actingAs(userWithRole('employee'))->post(route('admin.users.send-password-reset', $target))->assertForbidden();
});

test('valid broker tokens reset passwords without logging secrets', function () {
    $user = userWithRole('employee');
    $user->forceFill(['email' => 'reset@example.com', 'password' => 'OldPassword123'])->save();
    $token = Password::broker()->createToken($user);
    $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect(route('login'));
    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue();
    expect(AuditLog::query()->where('action', AuditAction::PasswordResetCompleted)->first()->toArray())->not->toContain($token);
});
