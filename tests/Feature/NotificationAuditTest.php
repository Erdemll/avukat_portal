<?php

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\Document;
use App\Notifications\DocumentUploadedNotification;
use App\Notifications\EventAssignedNotification;
use App\Notifications\EventUpdatedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('notifies the assigned active lawyer and audits event creation', function () {
    Notification::fake();
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');

    $this->actingAs($employee)->post('/events', eventPayload($lawyer))->assertRedirect();

    Notification::assertSentTo($lawyer, EventAssignedNotification::class);
    expect(AuditLog::query()->where('action', AuditAction::EventCreated)->exists())->toBeTrue();
});

it('does not notify inactive assigned lawyers', function () {
    Notification::fake();
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer', false);

    $this->actingAs($employee)->post('/events', eventPayload($lawyer))->assertSessionHasErrors('assigned_lawyer_id');
    Notification::assertNothingSent();
});

it('notifies event creators and managers after a lawyer update without notifying the author', function () {
    Notification::fake();
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $manager = userWithRole('manager');
    $event = legalEvent($employee, $lawyer);

    $this->actingAs($lawyer)->post(route('events.updates.store', $event), ['description' => 'İhtarname gönderildi.'])->assertRedirect();

    Notification::assertSentTo([$employee, $manager], EventUpdatedNotification::class);
    Notification::assertNotSentTo($lawyer, EventUpdatedNotification::class);
    expect(AuditLog::query()->where('action', AuditAction::EventUpdateCreated)->exists())->toBeTrue();
});

it('notifies the assigned lawyer after an employee uploads a document and audits it', function () {
    Notification::fake();
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent($employee, $lawyer);

    $this->actingAs($employee)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('sozlesme.pdf', 20, 'application/pdf')]])->assertRedirect();

    Notification::assertSentTo($lawyer, DocumentUploadedNotification::class);
    expect(AuditLog::query()->where('action', AuditAction::DocumentUploaded)->exists())->toBeTrue();
});

it('scopes notification reads to the authenticated user', function () {
    $owner = userWithRole('employee');
    $other = userWithRole('employee');
    $owner->notify(new EventAssignedNotification(legalEvent($owner, userWithRole('lawyer'))));
    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($other)->post(route('notifications.read', $notification))->assertNotFound();
    $this->actingAs($owner)->post(route('notifications.read', $notification))->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('audits successful and failed authentication without storing passwords', function () {
    $user = userWithRole('employee');
    $user->forceFill(['email' => 'audit@example.com', 'password' => 'Password123!'])->save();
    $this->post('/login', ['email' => $user->email, 'password' => 'Password123!'])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();
    expect(AuditLog::query()->where('action', AuditAction::UserLogin)->exists())->toBeTrue();
    Auth::logout();
    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
    $failed = AuditLog::query()->where('action', AuditAction::UserLoginFailed)->firstOrFail();
    expect($failed->description)->not->toContain('wrong-password');
});

it('audits successful document downloads but not forbidden attempts', function () {
    Storage::fake('legal_private');
    $owner = userWithRole('employee');
    $event = legalEvent($owner, userWithRole('lawyer'));
    $this->actingAs($owner)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('sozlesme.pdf', 20, 'application/pdf')]]);
    $document = Document::query()->firstOrFail();
    $this->actingAs(userWithRole('employee'))->get(route('documents.download', $document))->assertForbidden();
    expect(AuditLog::query()->where('action', AuditAction::DocumentDownloaded)->count())->toBe(0);
    $this->actingAs($owner)->get(route('documents.download', $document))->assertOk();
    expect(AuditLog::query()->where('action', AuditAction::DocumentDownloaded)->count())->toBe(1);
});

it('only allows managers to view audit logs', function () {
    $this->actingAs(userWithRole('employee'))->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get(route('audit-logs.index'))->assertOk();
});

it('marks only the authenticated users notifications as read', function () {
    $owner = userWithRole('employee');
    $other = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $owner->notify(new EventAssignedNotification(legalEvent($owner, $lawyer)));
    $other->notify(new EventAssignedNotification(legalEvent($other, $lawyer)));

    $this->actingAs($owner)->post(route('notifications.read-all'))->assertRedirect();

    expect($owner->unreadNotifications()->count())->toBe(0);
    expect($other->unreadNotifications()->count())->toBe(1);
});

it('audits logout and manager document deletion', function () {
    Storage::fake('legal_private');
    $owner = userWithRole('employee');
    $event = legalEvent($owner, userWithRole('lawyer'));
    $this->actingAs($owner)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('sozlesme.pdf', 20, 'application/pdf')]]);
    $document = Document::query()->firstOrFail();
    $manager = userWithRole('manager');

    $this->actingAs($manager)->delete(route('documents.destroy', $document))->assertRedirect();
    $this->actingAs($manager)->post(route('logout'))->assertRedirect(route('login'));

    expect(AuditLog::query()->where('action', AuditAction::DocumentDeleted)->exists())->toBeTrue();
    expect(AuditLog::query()->where('action', AuditAction::UserLogout)->exists())->toBeTrue();
});
