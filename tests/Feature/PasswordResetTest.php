<?php

use App\AuditAction;
use App\Models\AuditLog;
use Illuminate\Auth\Notifications\ResetPassword;
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
    $this->actingAs($manager)
        ->from(route('admin.users.edit', $target))
        ->followingRedirects()
        ->post(route('admin.users.send-password-reset', $target))
        ->assertOk()
        ->assertSee('Parola oluşturma/sıfırlama bağlantısı kullanıcıya gönderildi.');
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

test('reset links prefill the canonical account email and reset the password end to end', function () {
    Notification::fake();
    $user = userWithRole('employee');
    $user->forceFill(['email' => 'reset-link@example.com', 'password' => 'OldPassword123'])->save();

    $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();

    $sentNotification = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$sentNotification): bool {
        $sentNotification = $notification;

        return true;
    });

    $this->get(route('password.reset', ['token' => $sentNotification->token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('value="reset-link@example.com"', false)
        ->assertSee('readonly', false);

    $this->post(route('password.update'), [
        'token' => $sentNotification->token,
        'email' => $user->email,
        'password' => 'ChangedPassword123',
        'password_confirmation' => 'ChangedPassword123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('ChangedPassword123', $user->fresh()->password))->toBeTrue();
});

test('invalid reset tokens return a generic Turkish error without flashing the token', function () {
    $user = userWithRole('employee');

    $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'ChangedPassword123',
            'password_confirmation' => 'ChangedPassword123',
        ])
        ->assertSessionHasErrors('token')
        ->assertSessionMissing('_old_input.token');
});
