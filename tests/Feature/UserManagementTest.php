<?php

use App\AuditAction;
use App\Models\AuditLog;

it('restricts user management to managers', function () {
    $this->actingAs(userWithRole('employee'))->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get(route('admin.users.index'))->assertOk();
});

it('lets managers create users without a client supplied password', function () {
    $manager = userWithRole('manager');
    $role = role('employee');
    $this->actingAs($manager)->post(route('admin.users.store'), ['name' => 'Yeni Çalışan', 'email' => 'new@example.com', 'role_id' => $role->id, 'is_active' => true])->assertRedirect();
    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    expect(AuditLog::query()->where('action', AuditAction::UserCreated)->exists())->toBeTrue();
});

it('prevents deactivating the current manager and lawyers with open events', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager)->post(route('admin.users.deactivate', $manager))->assertSessionHasErrors('user');
    $lawyer = userWithRole('lawyer');
    legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs($manager)->post(route('admin.users.deactivate', $lawyer))->assertSessionHasErrors('user');
});

it('logs out users who were deactivated after login', function () {
    $user = userWithRole('employee');
    $user->forceFill(['is_active' => false])->save();
    $this->actingAs($user)->get('/events')->assertRedirect(route('login'));
});

it('does not expose registration or hard delete routes', function () {
    $this->get('/register')->assertNotFound();
    $deleteRoutes = collect(app('router')->getRoutes()->getRoutes())->filter(fn ($route) => in_array('DELETE', $route->methods(), true))->pluck('uri');
    expect($deleteRoutes)->not->toContain('admin/users/{user}');
});

it('shows the actual account action error instead of a generic form warning', function () {
    $manager = userWithRole('manager');
    $inactiveUser = userWithRole('employee');
    $inactiveUser->forceFill(['is_active' => false])->save();

    $this->actingAs($manager)
        ->from(route('admin.users.edit', $inactiveUser))
        ->followingRedirects()
        ->post(route('admin.users.send-password-reset', $inactiveUser))
        ->assertOk()
        ->assertSee('Pasif kullanıcıya parola oluşturma/sıfırlama bağlantısı gönderilemez.')
        ->assertDontSee('Lütfen işaretlenen alanları kontrol edin.');
});

it('shows Turkish validation details in the shared error summary', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)
        ->from(route('admin.users.create'))
        ->followingRedirects()
        ->post(route('admin.users.store'), [])
        ->assertOk()
        ->assertSee('ad alanı zorunludur.')
        ->assertSee('e-posta adresi alanı zorunludur.')
        ->assertSee('rol alanı zorunludur.');
});
