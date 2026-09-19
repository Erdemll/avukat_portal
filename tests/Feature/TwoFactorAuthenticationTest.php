<?php

use App\Models\TwoFactorChallenge;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\Notification;

it('lets an authenticated user enable and disable email two factor authentication with their password', function () {
    $user = userWithRole('manager');

    $this->actingAs($user)->get(route('security.show'))->assertOk()->assertSee('Güvenlik ayarları');

    $this->actingAs($user)->post(route('security.two-factor.enable'), ['current_password' => 'password'])->assertRedirect();
    expect($user->fresh()->hasTwoFactorAuthenticationEnabled())->toBeTrue();

    $this->actingAs($user)->delete(route('security.two-factor.disable'), ['current_password' => 'password'])->assertRedirect();
    expect($user->fresh()->hasTwoFactorAuthenticationEnabled())->toBeFalse();
});

it('keeps the user logged out until the emailed code is verified', function () {
    Notification::fake();
    $user = userWithRole('manager');
    $user->forceFill(['two_factor_enabled_at' => now()])->save();

    $this->post(route('login.store'), ['login_type' => 'email', 'email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
    $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Girişinizi doğrulayın');

    $code = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function (TwoFactorCodeNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('case-files.index'));
    $this->assertAuthenticatedAs($user);
    expect(TwoFactorChallenge::query()->firstOrFail()->consumed_at)->not->toBeNull();
});

it('counts invalid verification attempts without authenticating the user', function () {
    Notification::fake();
    $user = userWithRole('lawyer');
    $user->forceFill(['two_factor_enabled_at' => now()])->save();
    $this->post(route('login.store'), ['login_type' => 'email', 'email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');

    $this->assertGuest();
    expect(TwoFactorChallenge::query()->firstOrFail()->attempts)->toBe(1);
});
