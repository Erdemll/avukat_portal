<?php

use App\AuditAction;
use App\CaseFileStatus;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\CaseFileEvent;
use App\Models\CaseType;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\EventUpdate;
use App\Models\Hearing;
use App\Models\LegalTask;
use App\Models\User;
use App\Notifications\CaseDocumentsUploadedNotification;
use App\Notifications\CaseFileAssignedNotification;
use App\Notifications\CaseFileAssistantAssignedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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
    $this->actingAs($assistant)->get(route('case-files.index'))->assertSee($ownCase->title);
    $this->actingAs($assistant)->get(route('case-files.show', $ownCase))->assertSee($ownCase->title);
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

it('lets assistants reassign every case without gaining unrelated content access', function () {
    Notification::fake();
    $assistant = userWithRole('assistant');
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $foreignCase = legalCaseFile(userWithRole('manager'), [$firstLawyer]);
    $foreignCase->forceFill(['description' => 'Gizli dosya açıklaması'])->save();

    $this->actingAs($assistant)->get(route('case-files.index'))->assertDontSee($foreignCase->title);
    $this->actingAs($assistant)->get(route('case-assignments.index'))->assertSee($foreignCase->title);
    $this->actingAs($assistant)->get(route('case-files.show', $foreignCase))->assertForbidden();
    $this->actingAs($assistant)->get(route('case-files.assignments.show', $foreignCase))
        ->assertSee('Atamaları Güncelle')->assertDontSee('Gizli dosya açıklaması');
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

it('keeps the all-case assignment screen limited to managers and assistants', function () {
    $caseFile = legalCaseFile(userWithRole('manager'), [userWithRole('lawyer')]);

    $this->actingAs(userWithRole('employee'))->get(route('case-assignments.index'))->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->get(route('case-files.assignments.show', $caseFile))->assertForbidden();
});

it('lets a manager add an assistant to another creators case and grants content editing', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($manager)->put(route('case-files.assistants.update', $caseFile), [
        'assistant_ids' => [$assistant->id],
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect();

    expect($caseFile->assistants()->whereKey($assistant->id)->exists())->toBeTrue();
    Notification::assertSentTo($assistant, CaseFileAssistantAssignedNotification::class);
    expect(Gate::forUser($assistant)->allows('update', $caseFile))->toBeTrue();
    expect(AuditLog::query()->where('case_file_id', $caseFile->id)->where('action', AuditAction::CaseFileAssistantsChanged)->exists())->toBeTrue();
    $this->actingAs($assistant)->get(route('case-files.show', $caseFile))->assertOk()->assertSee('Genel Bilgileri Düzenle');
    $this->actingAs($assistant)->get(route('case-files.index'))->assertSee($caseFile->title);
    $this->actingAs($assistant)->get(route('case-files.edit', $caseFile))->assertOk();
    $this->actingAs($assistant)->put(route('case-files.update', $caseFile), [
        'case_type_id' => $caseFile->case_type_id,
        'title' => 'Asistan tarafından güncellendi',
        'priority' => 'normal',
        'opened_at' => $caseFile->opened_at->toDateString(),
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertRedirect();

    expect($caseFile->fresh()->title)->toBe('Asistan tarafından güncellendi');
    $this->actingAs($assistant)->post(route('case-files.parties.store', $caseFile), [
        'type' => 'company',
        'company_name' => 'Eklenen taraf',
        'role' => 'defendant',
        'side' => 'opposing',
    ])->assertRedirect();
    expect($caseFile->activeParties()->count())->toBe(1);
    $this->actingAs($assistant)->get(route('hearings.create', ['case_file' => $caseFile->id]))->assertOk();
    $this->actingAs($assistant)->get(route('deadlines.create', ['case_file' => $caseFile->id]))->assertOk();
    $this->actingAs($assistant)->get(route('legal-tasks.create', ['case_file' => $caseFile->id]))->assertOk();
});

it('removes content access when a manager removes an assistant from another creators case', function () {
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $caseFile->assistants()->attach($assistant);

    $this->actingAs($manager)->put(route('case-files.assistants.update', $caseFile), [
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect();

    expect($caseFile->assistants()->whereKey($assistant->id)->exists())->toBeFalse();
    expect(Gate::forUser($assistant)->allows('update', $caseFile))->toBeFalse();
    $this->actingAs($assistant)->get(route('case-files.index'))->assertDontSee($caseFile->title);
    $this->actingAs($assistant)->get(route('case-files.show', $caseFile))->assertForbidden();
    $this->actingAs($assistant)->get(route('case-files.assignments.show', $caseFile))->assertSee('Atamaları Güncelle');
    $this->actingAs($assistant)->get(route('case-files.edit', $caseFile))->assertForbidden();
});

it('prevents assistants from adding themselves and rejects non-assistant assignments', function () {
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($assistant)->put(route('case-files.assistants.update', $caseFile), [
        'assistant_ids' => [$assistant->id],
        'lock_version' => $caseFile->lock_version,
    ])->assertForbidden();
    $this->actingAs($manager)->put(route('case-files.assistants.update', $caseFile), [
        'assistant_ids' => [$lawyer->id],
        'lock_version' => $caseFile->lock_version,
    ])->assertSessionHasErrors('assistant_ids.0');

    expect($caseFile->assistants()->exists())->toBeFalse();
});

it('rejects assistant assignment changes based on an outdated case version', function () {
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);

    $this->actingAs($manager)->put(route('case-files.assistants.update', $caseFile), [
        'assistant_ids' => [$assistant->id],
        'lock_version' => $caseFile->lock_version + 1,
    ])->assertSessionHasErrors('lock_version');

    expect($caseFile->assistants()->exists())->toBeFalse();
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

it('allows an added assistant to download case documents and revokes that access when removed', function () {
    Notification::fake();
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $document = Document::factory()->create(['case_file_id' => $caseFile, 'event_id' => null, 'uploaded_by' => $manager]);
    Storage::disk('legal_private')->put($document->path, 'Belge içeriği');

    $this->actingAs($manager)->put(route('case-files.assistants.update', $caseFile), [
        'assistant_ids' => [$assistant->id],
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect();

    $this->actingAs($assistant)->get(route('documents.download', $document))->assertOk();
    $this->actingAs($assistant)->get(route('legal-documents.index'))->assertSee($document->original_name);

    $this->actingAs($manager)->put(route('case-files.assistants.update', $caseFile), [
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertRedirect();

    $this->actingAs($assistant)->get(route('documents.download', $document))->assertForbidden();
    $this->actingAs($assistant)->get(route('legal-documents.index'))->assertDontSee($document->original_name);
});

it('lets an added assistant update a linked event but not an unrelated or shared event', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $caseFile->assistants()->attach($assistant);
    $event = legalEvent(userWithRole('employee'), $lawyer);
    CaseFileEvent::factory()->create(['case_file_id' => $caseFile, 'event_id' => $event, 'linked_by' => $manager]);
    $unrelatedEvent = legalEvent(userWithRole('employee'), $lawyer);

    $this->actingAs($assistant)->get(route('events.show', $event))->assertSee($event->title);
    $this->actingAs($assistant)->post(route('events.updates.store', $event), ['description' => 'Asistan işlemi'])->assertRedirect();
    expect(EventUpdate::query()->where('event_id', $event->id)->count())->toBe(1);
    $this->actingAs($assistant)->post(route('events.updates.store', $unrelatedEvent), ['description' => 'Yetkisiz'])->assertForbidden();

    $otherCase = legalCaseFile($manager, [$lawyer]);
    CaseFileEvent::factory()->create(['case_file_id' => $otherCase, 'event_id' => $event, 'linked_by' => $manager]);
    $this->actingAs($assistant)->post(route('events.updates.store', $event), ['description' => 'Paylaşılan dosya'])->assertForbidden();
    expect(EventUpdate::query()->where('event_id', $event->id)->count())->toBe(1);
});

it('clears assistant case links when the account changes role', function () {
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $caseFile->assistants()->attach($assistant);

    $this->actingAs($manager)->put(route('admin.users.update', $assistant), [
        'name' => $assistant->name,
        'email' => $assistant->email,
        'role_id' => role('employee')->id,
    ])->assertRedirect();

    expect($caseFile->assistants()->whereKey($assistant->id)->exists())->toBeFalse();

    $this->actingAs($manager)->put(route('admin.users.update', $assistant), [
        'name' => $assistant->name,
        'email' => $assistant->email,
        'role_id' => role('assistant')->id,
    ])->assertRedirect();

    $this->actingAs($assistant->fresh())->get(route('case-files.show', $caseFile))->assertForbidden();
});

it('notifies an assistant about documents in a linked case', function () {
    Notification::fake();
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $caseFile->assistants()->attach($assistant);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('evrak.pdf', 20, 'application/pdf')],
        'document_type' => 'petition',
    ])->assertRedirect();

    Notification::assertSentTo($assistant, CaseDocumentsUploadedNotification::class);
});

it('notifies the assistant creator about documents even without an assistant link', function () {
    Notification::fake();
    Storage::fake('legal_private');
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($assistant, [$lawyer]);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('evrak.pdf', 20, 'application/pdf')],
        'document_type' => 'petition',
    ])->assertRedirect();

    Notification::assertSentTo($assistant, CaseDocumentsUploadedNotification::class);
});

it('lets a linked assistant change status and create case operations', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $assistant = userWithRole('assistant');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $caseFile->assistants()->attach($assistant);

    $this->actingAs($assistant)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'resolved',
        'reason' => 'İşlem tamamlandı',
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect();
    $this->actingAs($assistant)->post(route('hearings.store'), [
        'case_file_id' => $caseFile->id,
        'lawyer_id' => $lawyer->id,
        'title' => 'Ön inceleme',
        'hearing_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'status' => 'scheduled',
    ])->assertRedirect();
    $this->actingAs($assistant)->post(route('deadlines.store'), [
        'case_file_id' => $caseFile->id,
        'assigned_lawyer_id' => $lawyer->id,
        'title' => 'Cevap süresi',
        'due_at' => now()->addDays(14)->format('Y-m-d H:i:s'),
        'status' => 'open',
    ])->assertRedirect();
    $this->actingAs($assistant)->post(route('legal-tasks.store'), [
        'case_file_id' => $caseFile->id,
        'assigned_to' => $assistant->id,
        'title' => 'Dilekçe hazırla',
        'priority' => 'high',
        'status' => 'pending',
    ])->assertRedirect();

    expect($caseFile->fresh()->status)->toBe(CaseFileStatus::Resolved);
    expect(Hearing::query()->where('case_file_id', $caseFile->id)->exists())->toBeTrue();
    expect(Deadline::query()->where('case_file_id', $caseFile->id)->exists())->toBeTrue();
    expect(LegalTask::query()->where('case_file_id', $caseFile->id)->exists())->toBeTrue();
});
