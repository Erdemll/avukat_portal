<?php

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\Document;
use App\Models\User;
use App\Notifications\CaseFileAssignedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

it('lets managers create an assistant account without lawyer credentials', function () {
    $this->seed(RoleSeeder::class);
    $manager = userWithRole('manager');
    $assistantRole = role('assistant');

    $this->actingAs($manager)->post(route('admin.users.store'), [
        'name' => 'Hukuk Asistanı',
        'email' => 'assistant@example.com',
        'role_id' => $assistantRole->id,
        'is_active' => true,
    ])->assertRedirect();

    $assistant = User::query()->where('email', 'assistant@example.com')->firstOrFail();
    expect($assistant->isAssistant())->toBeTrue();
    expect($assistant->tc_kimlik_no)->toBeNull();
    $this->actingAs($assistant)->get(route('admin.users.index'))->assertForbidden();
});

it('signs assistants in through the email login', function () {
    $assistant = userWithRole('assistant');

    $this->post(route('login.store'), [
        'login_type' => 'email',
        'email' => $assistant->email,
        'password' => 'password',
    ])->assertRedirect(route('case-files.index'));

    $this->assertAuthenticatedAs($assistant);
});

it('changes an employee to assistant without requiring an identity number', function () {
    $manager = userWithRole('manager');
    $employee = userWithRole('employee');

    $this->actingAs($manager)->put(route('admin.users.update', $employee), [
        'name' => $employee->name,
        'email' => $employee->email,
        'role_id' => role('assistant')->id,
    ])->assertRedirect(route('admin.users.index'));

    expect($employee->fresh()->isAssistant())->toBeTrue();
    expect($employee->fresh()->tc_kimlik_no)->toBeNull();
});

it('changes an unassigned lawyer to assistant and removes the former identity number', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $lawyer->forceFill(['tc_kimlik_no' => '12345678901'])->save();

    $this->actingAs($manager)->put(route('admin.users.update', $lawyer), [
        'name' => $lawyer->name,
        'email' => $lawyer->email,
        'role_id' => role('assistant')->id,
    ])->assertRedirect(route('admin.users.index'));

    expect($lawyer->fresh()->isAssistant())->toBeTrue();
    expect($lawyer->fresh()->tc_kimlik_no)->toBeNull();
});

it('keeps lawyer identity required while the lawyer role is selected', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');

    $this->actingAs($manager)->put(route('admin.users.update', $lawyer), [
        'name' => $lawyer->name,
        'email' => $lawyer->email,
        'role_id' => $lawyer->role_id,
    ])->assertSessionHasErrors('tc_kimlik_no');
});

it('does not convert a lawyer with active case assignments into an assistant', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $lawyer->forceFill(['tc_kimlik_no' => '12345678901'])->save();
    legalCaseFile($manager, [$lawyer]);

    $this->actingAs($manager)->put(route('admin.users.update', $lawyer), [
        'name' => $lawyer->name,
        'email' => $lawyer->email,
        'role_id' => role('assistant')->id,
    ])->assertSessionHasErrors('role_id');

    expect($lawyer->fresh()->isLawyer())->toBeTrue();
    expect($lawyer->fresh()->tc_kimlik_no)->toBe('12345678901');
});

it('lets assistants create cases with real lawyers and edit only their own cases', function () {
    Notification::fake();
    $assistant = userWithRole('assistant');
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $foreignCase = legalCaseFile(userWithRole('manager'), [$firstLawyer]);

    $this->actingAs($assistant)->post(route('case-files.store'), [
        'case_type_id' => CaseType::factory()->create()->id,
        'title' => 'Asistanın dosyası',
        'priority' => 'normal',
        'opened_at' => '2026-09-30',
        'lawyer_ids' => [$firstLawyer->id, $secondLawyer->id],
        'lead_lawyer_id' => $secondLawyer->id,
    ])->assertRedirect();

    $ownCase = CaseFile::query()->where('created_by', $assistant->id)->firstOrFail();
    expect($ownCase->activeLawyers()->pluck('users.id')->all())->toEqualCanonicalizing([$firstLawyer->id, $secondLawyer->id]);
    expect($ownCase->activeLawyers()->wherePivot('role', 'lead')->first()->is($secondLawyer))->toBeTrue();
    expect($ownCase->activeLawyers()->whereKey($assistant->id)->exists())->toBeFalse();
    Notification::assertSentTo([$firstLawyer, $secondLawyer], CaseFileAssignedNotification::class);

    $this->actingAs($assistant)->get(route('case-files.edit', $ownCase))->assertOk();
    $this->actingAs($assistant)->put(route('case-files.update', $ownCase), [
        'case_type_id' => $ownCase->case_type_id,
        'title' => 'Asistanın güncel dosyası',
        'priority' => 'normal',
        'opened_at' => '2026-09-30',
        'lock_version' => $ownCase->lock_version,
    ])->assertRedirect();
    expect($ownCase->fresh()->title)->toBe('Asistanın güncel dosyası');
    $this->actingAs($assistant)->post(route('case-files.parties.store', $ownCase), [
        'type' => 'company',
        'company_name' => 'Yeni karşı taraf',
        'role' => 'defendant',
        'side' => 'opposing',
    ])->assertRedirect();
    expect($ownCase->activeParties()->count())->toBe(1);
    $this->actingAs($assistant)->get(route('case-files.edit', $foreignCase))->assertForbidden();
    $this->actingAs($assistant)->put(route('case-files.update', $foreignCase), [
        'case_type_id' => $foreignCase->case_type_id,
        'title' => 'Yetkisiz değişiklik',
        'priority' => 'normal',
        'opened_at' => '2026-09-30',
        'lock_version' => $foreignCase->lock_version,
    ])->assertForbidden();
    expect($foreignCase->fresh()->title)->not->toBe('Yetkisiz değişiklik');
});

it('lets assistants reassign every case without gaining content write access', function () {
    Notification::fake();
    $assistant = userWithRole('assistant');
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $foreignCase = legalCaseFile(userWithRole('manager'), [$firstLawyer]);
    $foreignCase->forceFill(['description' => 'Gizli dosya açıklaması'])->save();

    $this->actingAs($assistant)->get(route('case-files.index'))->assertOk()->assertSee($foreignCase->title);
    $this->actingAs($assistant)->get(route('case-files.show', $foreignCase))
        ->assertOk()->assertSee('Atamaları Güncelle')->assertDontSee('Gizli dosya açıklaması');
    $this->actingAs($assistant)->put(route('case-files.assignments.update', $foreignCase), [
        'lawyer_ids' => [$secondLawyer->id],
        'lead_lawyer_id' => $secondLawyer->id,
        'reason' => 'Dosya devri',
        'lock_version' => $foreignCase->lock_version,
    ])->assertRedirect();

    expect($foreignCase->activeLawyers()->pluck('users.id')->all())->toBe([$secondLawyer->id]);
    expect(AuditLog::query()->where('case_file_id', $foreignCase->id)->where('action', AuditAction::CaseFileAssignmentsChanged)->exists())->toBeTrue();
    Notification::assertSentTo($secondLawyer, CaseFileAssignedNotification::class);

    $foreignDocument = Document::factory()->create(['case_file_id' => $foreignCase, 'event_id' => null, 'uploaded_by' => $firstLawyer]);
    $this->actingAs($assistant)->get(route('documents.download', $foreignDocument))->assertForbidden();
    $this->actingAs($assistant)->delete(route('documents.destroy', $foreignDocument))->assertForbidden();
    expect($foreignDocument->fresh()->deleted_at)->toBeNull();

    $this->actingAs($assistant)->post(route('case-files.parties.store', $foreignCase), [
        'type' => 'company',
        'company_name' => 'Yetkisiz taraf',
        'role' => 'defendant',
        'side' => 'opposing',
    ])->assertForbidden();
    $this->actingAs($assistant)->patch(route('case-files.status.update', $foreignCase), [
        'status' => 'resolved',
        'reason' => 'Yetkisiz',
        'lock_version' => $foreignCase->fresh()->lock_version,
    ])->assertForbidden();
    expect($foreignCase->fresh()->status->value)->toBe('active');
});

it('rejects assistants as assigned lawyers and keeps lawyer assignment changes forbidden', function () {
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile(userWithRole('manager'), [$lawyer]);

    $this->actingAs($assistant)->put(route('case-files.assignments.update', $caseFile), [
        'lawyer_ids' => [$assistant->id],
        'lead_lawyer_id' => $assistant->id,
        'lock_version' => $caseFile->lock_version,
    ])->assertSessionHasErrors(['lawyer_ids.0', 'lead_lawyer_id']);
    expect($caseFile->activeLawyers()->first()->is($lawyer))->toBeTrue();

    $this->actingAs($lawyer)->put(route('case-files.assignments.update', $caseFile), [
        'lawyer_ids' => [$lawyer->id],
        'lead_lawyer_id' => $lawyer->id,
        'lock_version' => $caseFile->lock_version,
    ])->assertForbidden();
});

it('allows assistants and lawyers to message each other', function () {
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');

    $this->actingAs($assistant)->get(route('messages.index'))->assertOk();
    $this->actingAs($assistant)->postJson(route('messages.conversations.store'), ['user_id' => $lawyer->id])->assertOk();
    $this->actingAs($lawyer)->get(route('messages.index'))->assertOk()->assertSee('data-lawyer-id="'.$assistant->id.'"', false);
});
