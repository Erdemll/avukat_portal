<?php

use App\AuditAction;
use App\CaseAssignmentRequestStatus;
use App\Models\AuditLog;
use App\Models\CaseAssignmentRequest;
use App\Models\CaseFileParty;
use App\Models\Client;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Event;
use App\Models\Hearing;
use App\Models\LegalTask;
use App\Models\Party;
use App\Models\User;
use App\Notifications\EventAssignedNotification;
use App\Services\AuditService;
use App\Services\LawyerRetirementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;

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

it('does not create inactive lawyer accounts that retain personal identifiers', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)->post(route('admin.users.store'), [
        'name' => 'Pasif Avukat',
        'email' => 'inactive@example.com',
        'tc_kimlik_no' => '12345678901',
        'role_id' => role('lawyer')->id,
        'is_active' => false,
    ])->assertSessionHasErrors('is_active');

    $this->assertDatabaseMissing('users', ['email' => 'inactive@example.com']);
});

it('protects the current manager and requires a replacement to retire a lawyer', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager)->post(route('admin.users.deactivate', $manager))->assertSessionHasErrors('user');
    $lawyer = userWithRole('lawyer');
    legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs($manager)->post(route('admin.users.deactivate', $lawyer))->assertSessionHasErrors('replacement_lawyer_id');
    expect($lawyer->fresh()->is_active)->toBeTrue();
});

it('retires a lawyer by transferring current responsibility while preserving the historical authors', function () {
    $manager = userWithRole('manager');
    $oldLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $colleague = userWithRole('lawyer');
    $oldLawyer->forceFill(['tc_kimlik_no' => '12345678901', 'phone' => '05000000000', 'two_factor_enabled_at' => now()])->save();
    $oldEmail = $oldLawyer->email;
    $caseFile = legalCaseFile($manager, [$oldLawyer, $replacement, $colleague]);
    $closedCase = legalCaseFile($manager, [$oldLawyer]);
    $closedCase->forceFill(['status' => 'closed'])->save();
    $event = legalEvent(userWithRole('employee'), $oldLawyer);
    $hearing = Hearing::factory()->create(['case_file_id' => $caseFile, 'lawyer_id' => $oldLawyer, 'created_by' => $oldLawyer]);
    $completedHearing = Hearing::factory()->create(['case_file_id' => $caseFile, 'lawyer_id' => $oldLawyer, 'status' => 'completed', 'created_by' => $oldLawyer]);
    $deadline = Deadline::factory()->create(['case_file_id' => $caseFile, 'assigned_lawyer_id' => $oldLawyer, 'created_by' => $oldLawyer]);
    $task = LegalTask::factory()->create(['case_file_id' => null, 'assigned_to' => $oldLawyer, 'created_by' => $oldLawyer]);
    $document = Document::factory()->create(['event_id' => null, 'case_file_id' => $caseFile, 'uploaded_by' => $oldLawyer]);
    $version = DocumentVersion::factory()->create(['document_id' => $document, 'uploaded_by' => $oldLawyer]);
    $pendingRequest = CaseAssignmentRequest::factory()->create(['case_file_id' => $caseFile, 'requested_by' => $oldLawyer, 'requested_to' => $colleague]);
    DB::table('password_reset_tokens')->insert(['email' => $oldEmail, 'token' => 'obsolete']);

    $this->actingAs($manager)->post(route('admin.users.deactivate', $oldLawyer), [
        'replacement_lawyer_id' => $replacement->id,
    ])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('users', ['id' => $oldLawyer->id, 'name' => $oldLawyer->name, 'email' => null, 'tc_kimlik_no' => null, 'phone' => null, 'is_active' => false]);
    expect(User::factory()->create(['email' => $oldEmail])->email)->toBe($oldEmail);
    expect($oldLawyer->fresh()->two_factor_enabled_at)->toBeNull();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);
    expect($caseFile->activeLawyers()->pluck('users.id')->all())->toEqualCanonicalizing([$replacement->id, $colleague->id]);
    expect($caseFile->activeLawyers()->wherePivot('role', 'lead')->first()->is($replacement))->toBeTrue();
    expect($closedCase->activeLawyers()->first()->is($replacement))->toBeTrue();
    $this->assertDatabaseHas('case_file_assignments', ['case_file_id' => $caseFile->id, 'lawyer_id' => $oldLawyer->id, 'role' => 'lead', 'active_marker' => null]);
    expect($event->fresh()->assigned_lawyer_id)->toBe($replacement->id);
    expect($hearing->fresh()->lawyer_id)->toBe($replacement->id);
    expect($completedHearing->fresh()->lawyer_id)->toBe($oldLawyer->id);
    expect($deadline->fresh()->assigned_lawyer_id)->toBe($replacement->id);
    expect($task->fresh()->assigned_to)->toBe($replacement->id);
    expect($document->fresh()->uploaded_by)->toBe($oldLawyer->id);
    expect($version->fresh()->uploaded_by)->toBe($oldLawyer->id);
    expect($pendingRequest->fresh()->status)->toBe(CaseAssignmentRequestStatus::Cancelled);
    expect(AuditLog::query()->where('action', AuditAction::UserDeactivated)->where('auditable_id', $oldLawyer->id)->exists())->toBeTrue();
});

it('lets a successor manage a former lawyers case documents and client without rewriting their authorship', function () {
    $manager = userWithRole('manager');
    $oldLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$oldLawyer]);
    $client = Client::factory()->create(['created_by' => $oldLawyer]);
    CaseFileParty::factory()->create(['case_file_id' => $caseFile, 'party_id' => $client->party_id, 'added_by' => $oldLawyer]);
    $document = Document::factory()->create(['event_id' => null, 'case_file_id' => $caseFile, 'uploaded_by' => $oldLawyer]);

    $this->actingAs($manager)->post(route('admin.users.deactivate', $oldLawyer), ['replacement_lawyer_id' => $replacement->id])->assertRedirect();

    expect($replacement->can('update', $client))->toBeTrue();
    expect($replacement->can('uploadVersion', $document))->toBeTrue();
    expect($replacement->can('delete', $document))->toBeTrue();
    expect($document->fresh()->uploaded_by)->toBe($oldLawyer->id);
    $this->actingAs($replacement)->get(route('case-files.show', $caseFile))
        ->assertSee($replacement->name)->assertDontSee('Lider: '.$oldLawyer->name)
        ->assertSee('Geçmiş Avukat Atamaları')->assertSee($oldLawyer->name.' · Lider Avukat');
});

it('transfers unlinked client responsibility without changing the creator or exposing unrelated cases', function () {
    $manager = userWithRole('manager');
    $oldLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $unrelatedLawyer = userWithRole('lawyer');
    $party = Party::factory()->create(['created_by' => $oldLawyer]);
    $client = Client::factory()->create(['party_id' => $party, 'created_by' => $oldLawyer]);
    $otherParty = Party::factory()->create(['created_by' => $oldLawyer]);
    $otherClient = Client::factory()->create(['party_id' => $otherParty, 'created_by' => $manager]);
    $otherClient->forceFill(['responsible_lawyer_id' => $oldLawyer->id])->save();
    $unrelatedCase = legalCaseFile($manager, [$unrelatedLawyer]);
    $linkedClient = Client::factory()->create(['created_by' => $oldLawyer]);
    CaseFileParty::factory()->create(['case_file_id' => $unrelatedCase, 'party_id' => $linkedClient->party_id, 'added_by' => $manager]);

    $this->actingAs($manager)->post(route('admin.users.deactivate', $oldLawyer), [
        'replacement_lawyer_id' => $replacement->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('clients', ['id' => $client->id, 'created_by' => $oldLawyer->id, 'responsible_lawyer_id' => $replacement->id]);
    $this->assertDatabaseHas('parties', ['id' => $party->id, 'created_by' => $oldLawyer->id]);
    expect($otherClient->fresh()->responsible_lawyer_id)->toBe($replacement->id);
    expect($replacement->can('update', $client->refresh()))->toBeTrue();
    expect($replacement->can('delete', $client))->toBeTrue();
    $this->actingAs($replacement)->get(route('clients.show', $client))->assertOk()->assertSee($party->display_name);
    $this->actingAs($replacement)->get(route('clients.show', $linkedClient))->assertForbidden();
});

it('does not return transferred cases or clients to the previous lawyer after reactivation', function () {
    $manager = userWithRole('manager');
    $previousLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$previousLawyer]);
    $party = Party::factory()->create(['created_by' => $previousLawyer]);
    $client = Client::factory()->create(['party_id' => $party, 'created_by' => $previousLawyer]);
    app(LawyerRetirementService::class)->retire($previousLawyer, $replacement, $manager);

    $this->actingAs($manager)->post(route('admin.users.activate', $previousLawyer), [
        'email' => 'returned@example.com',
        'tc_kimlik_no' => '23456789012',
    ])->assertRedirect();

    $this->actingAs($previousLawyer->refresh())->get(route('case-files.show', $caseFile))->assertForbidden();
    $this->actingAs($previousLawyer)->get(route('clients.show', $client))->assertForbidden();
    expect(Party::query()->visibleTo($previousLawyer)->whereKey($party)->exists())->toBeFalse();
    $this->actingAs($replacement)->get(route('clients.show', $client))->assertOk();
});

it('does not let a reactivated lawyer see or edit personal tasks transferred to the successor', function () {
    $manager = userWithRole('manager');
    $previousLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $task = LegalTask::factory()->create([
        'case_file_id' => null,
        'assigned_to' => $previousLawyer,
        'created_by' => $previousLawyer,
        'title' => 'Devredilen kişisel görev',
    ]);
    app(LawyerRetirementService::class)->retire($previousLawyer, $replacement, $manager);
    $previousLawyer->refresh()->forceFill(['email' => 'returned@example.com', 'tc_kimlik_no' => '23456789012', 'is_active' => true])->save();

    expect($task->fresh()->created_by)->toBe($previousLawyer->id);
    expect($task->fresh()->assigned_to)->toBe($replacement->id);
    $this->actingAs($previousLawyer)->get(route('legal-tasks.index'))->assertDontSee('Devredilen kişisel görev');
    $this->actingAs($previousLawyer)->get(route('legal-tasks.edit', $task))->assertForbidden();
    $this->actingAs($previousLawyer)->put(route('legal-tasks.update', $task), [])->assertForbidden();
    $this->actingAs($replacement)->get(route('legal-tasks.edit', $task))->assertOk();
});

it('reassigns deleted events without notifying the successor until they are restored', function () {
    $manager = userWithRole('manager');
    $previousLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $previousLawyer);
    $event->delete();
    Notification::fake([EventAssignedNotification::class]);

    $this->actingAs($manager)->post(route('admin.users.deactivate', $previousLawyer), [
        'replacement_lawyer_id' => $replacement->id,
    ])->assertRedirect();

    $storedEvent = Event::query()->withTrashed()->findOrFail($event->id);
    expect($storedEvent->trashed())->toBeTrue();
    expect($storedEvent->assigned_lawyer_id)->toBe($replacement->id);
    Notification::assertNotSentTo($replacement, EventAssignedNotification::class);
    $event->restore();
    $this->actingAs($replacement)->get(route('events.show', $event))->assertOk();
    $previousLawyer->refresh()->forceFill(['email' => 'returned@example.com', 'tc_kimlik_no' => '23456789012', 'is_active' => true])->save();
    $this->actingAs($previousLawyer)->get(route('events.show', $event))->assertForbidden();
});

it('rejects an inactive non lawyer or the same lawyer as a retirement successor', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $inactiveLawyer = userWithRole('lawyer', false);

    $this->actingAs($manager)->post(route('admin.users.deactivate', $lawyer), ['replacement_lawyer_id' => $lawyer->id])->assertSessionHasErrors('replacement_lawyer_id');
    $this->actingAs($manager)->post(route('admin.users.deactivate', $lawyer), ['replacement_lawyer_id' => $inactiveLawyer->id])->assertSessionHasErrors('replacement_lawyer_id');
    $this->actingAs($manager)->post(route('admin.users.deactivate', $lawyer), ['replacement_lawyer_id' => userWithRole('employee')->id])->assertSessionHasErrors('replacement_lawyer_id');
    expect($lawyer->fresh()->is_active)->toBeTrue();
});

it('does not let a non manager transfer another lawyers work', function () {
    $lawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $caseFile = legalCaseFile(userWithRole('manager'), [$lawyer]);

    $this->actingAs(userWithRole('employee'))->post(route('admin.users.deactivate', $lawyer), [
        'replacement_lawyer_id' => $replacement->id,
    ])->assertForbidden();

    expect($lawyer->fresh()->is_active)->toBeTrue();
    expect($caseFile->activeLawyers()->first()->is($lawyer))->toBeTrue();
});

it('rolls back case transfers if the retirement audit fails', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $originalEmail = $lawyer->email;
    $this->mock(AuditService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('log')->andThrow(new RuntimeException('Audit kaydı başarısız.'));
    });

    expect(fn () => app(LawyerRetirementService::class)->retire($lawyer, $replacement, $manager))
        ->toThrow(RuntimeException::class);

    expect($caseFile->activeLawyers()->first()->is($lawyer))->toBeTrue();
    expect($lawyer->fresh()->email)->toBe($originalEmail);
    expect($lawyer->fresh()->is_active)->toBeTrue();
});

it('does not restore personal data through the retired lawyers edit form', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer', false);
    $lawyer->forceFill(['email' => null, 'tc_kimlik_no' => null, 'phone' => null])->save();

    $this->actingAs($manager)->put(route('admin.users.update', $lawyer), [
        'name' => 'Arşiv Adı',
        'email' => 'leaked@example.com',
        'tc_kimlik_no' => '12345678901',
    ])->assertSessionHasErrors(['email', 'tc_kimlik_no']);

    $this->assertDatabaseHas('users', ['id' => $lawyer->id, 'email' => null, 'tc_kimlik_no' => null]);
    $this->actingAs($manager)->get(route('admin.users.edit', $lawyer))
        ->assertSee('Hesap yeniden açılırken e-posta ve TC kimlik numarası girilmelidir.')
        ->assertDontSee('leaked@example.com');
});

it('reactivates a retired lawyer only with a unique email and identity number', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer', false);
    $lawyer->forceFill(['email' => null, 'tc_kimlik_no' => null, 'phone' => null])->save();
    $otherLawyer = userWithRole('lawyer');
    $otherLawyer->forceFill(['tc_kimlik_no' => '12345678901'])->save();

    $this->actingAs($manager)->post(route('admin.users.activate', $lawyer))->assertSessionHasErrors(['email', 'tc_kimlik_no']);
    $this->actingAs($manager)->post(route('admin.users.activate', $lawyer), [
        'email' => $otherLawyer->email,
        'tc_kimlik_no' => '12345678901',
    ])->assertSessionHasErrors(['email', 'tc_kimlik_no']);
    expect($lawyer->fresh()->is_active)->toBeFalse();

    $this->actingAs($manager)->post(route('admin.users.activate', $lawyer), [
        'email' => 'reactivated@example.com',
        'tc_kimlik_no' => '23456789012',
    ])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('users', ['id' => $lawyer->id, 'is_active' => true, 'email' => 'reactivated@example.com', 'tc_kimlik_no' => '23456789012']);
    expect($lawyer->fresh()->activeCaseFiles()->count())->toBe(0);
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
