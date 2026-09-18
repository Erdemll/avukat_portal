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
