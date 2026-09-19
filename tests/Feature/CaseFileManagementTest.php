<?php

use App\AuditAction;
use App\CaseAssignmentRole;
use App\CaseFileStatus;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Party;
use App\Notifications\CaseFileAssignedNotification;
use Illuminate\Support\Facades\Notification;

it('creates a legal case with clients proceeding and multiple lawyers atomically', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $leadLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $caseType = CaseType::factory()->create(['category' => 'lawsuit']);
    $party = Party::factory()->company()->create(['created_by' => $manager]);
    Client::factory()->create(['party_id' => $party, 'created_by' => $manager]);

    $this->actingAs($manager)->post(route('case-files.store'), [
        'case_type_id' => $caseType->id,
        'title' => 'ABC Ltd. işçilik alacağı davası',
        'priority' => 'high',
        'description' => 'İşçilik alacaklarına ilişkin uyuşmazlık.',
        'opened_at' => '2026-09-19',
        'lawyer_ids' => [$leadLawyer->id, $secondLawyer->id],
        'lead_lawyer_id' => $leadLawyer->id,
        'client_party_ids' => [$party->id],
        'proceeding_type' => 'lawsuit',
        'courthouse' => 'Konya Adliyesi',
        'authority_name' => 'Konya 3. İş Mahkemesi',
        'principal_year' => 2026,
        'principal_number' => '315',
    ])->assertRedirect();

    $caseFile = CaseFile::query()->firstOrFail();
    expect($caseFile->case_no)->toBe('TPN-2026-000001');
    expect($caseFile->activeLawyers()->count())->toBe(2);
    expect($caseFile->activeLawyers()->wherePivot('role', CaseAssignmentRole::Lead)->first()->is($leadLawyer))->toBeTrue();
    expect($caseFile->activeParties()->first()->is($party))->toBeTrue();
    expect($caseFile->proceedings()->first()->authority_name)->toBe('Konya 3. İş Mahkemesi');
    expect($caseFile->statusHistories()->count())->toBe(1);
    expect(AuditLog::query()->where('case_file_id', $caseFile->id)->where('action', AuditAction::CaseFileCreated)->exists())->toBeTrue();
    Notification::assertSentTo([$leadLawyer, $secondLawyer], CaseFileAssignedNotification::class);
});

it('assigns a lawyer who creates a legal case as its lead', function () {
    Notification::fake();
    $lawyer = userWithRole('lawyer');

    $this->actingAs($lawyer)->post(route('case-files.store'), [
        'case_type_id' => CaseType::factory()->create()->id,
        'title' => 'Danışmanlık dosyası',
        'priority' => 'normal',
        'opened_at' => '2026-09-19',
    ])->assertRedirect();

    $caseFile = CaseFile::query()->firstOrFail();
    expect($caseFile->activeLawyers()->first()->is($lawyer))->toBeTrue();
    expect($caseFile->activeLawyers()->first()->pivot->role)->toBe(CaseAssignmentRole::Lead);
    Notification::assertNothingSent();
});

it('converts an assigned legal request only once', function () {
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $payload = [
        'case_type_id' => CaseType::factory()->create()->id,
        'title' => 'Tahsil edilemeyen alacak',
        'priority' => 'high',
        'opened_at' => '2026-09-19',
    ];

    $this->actingAs($lawyer)->post(route('events.case-file.store', $event), $payload)->assertRedirect();

    $caseFile = CaseFile::query()->firstOrFail();
    expect($event->caseFiles()->first()->is($caseFile))->toBeTrue();
    expect($event->caseFiles()->first()->pivot->relation_type->value)->toBe('origin');

    $this->actingAs($lawyer)->post(route('events.case-file.store', $event), $payload)->assertSessionHasErrors('event');
    expect(CaseFile::query()->count())->toBe(1);
});

it('enforces legal case status transitions and optimistic locking', function () {
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile(userWithRole('manager'), [$lawyer]);
    $staleVersion = $caseFile->lock_version;

    $this->actingAs($lawyer)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'resolved',
        'reason' => 'Karar alındı.',
        'lock_version' => $staleVersion,
    ])->assertRedirect();

    expect($caseFile->fresh()->status)->toBe(CaseFileStatus::Resolved);
    $this->actingAs($lawyer)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'active',
        'lock_version' => $staleVersion,
    ])->assertSessionHasErrors('lock_version');
    expect($caseFile->fresh()->statusHistories()->count())->toBe(1);
});

it('requires valid status transitions reasons and manager approval for reopening closed cases', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'closed',
        'reason' => 'Doğrudan kapatma',
        'lock_version' => $caseFile->lock_version,
    ])->assertSessionHasErrors('status');
    $this->actingAs($lawyer)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'resolved',
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertSessionHasErrors('reason');

    $this->actingAs($manager)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'resolved',
        'reason' => 'Karar alındı.',
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertRedirect();
    $this->actingAs($manager)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'closed',
        'reason' => 'Dosya işlemleri tamamlandı.',
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertRedirect();
    $this->actingAs($lawyer)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'active',
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertSessionHasErrors('status');
    $this->actingAs($manager)->patch(route('case-files.status.update', $caseFile), [
        'status' => 'active',
        'lock_version' => $caseFile->fresh()->lock_version,
    ])->assertRedirect();

    expect($caseFile->fresh()->status)->toBe(CaseFileStatus::Active);
    expect($caseFile->fresh()->closed_at)->toBeNull();
});

it('lets managers change the legal team and notifies newly assigned lawyers', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $firstLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$firstLawyer]);

    $this->actingAs($manager)->put(route('case-files.assignments.update', $caseFile), [
        'lawyer_ids' => [$firstLawyer->id, $secondLawyer->id],
        'lead_lawyer_id' => $secondLawyer->id,
        'reason' => 'Ekip genişletildi.',
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect();

    expect($caseFile->activeLawyers()->count())->toBe(2);
    expect($caseFile->activeLawyers()->wherePivot('role', 'lead')->first()->is($secondLawyer))->toBeTrue();
    Notification::assertSentTo($secondLawyer, CaseFileAssignedNotification::class);
    Notification::assertNotSentTo($firstLawyer, CaseFileAssignedNotification::class);
});

it('updates general case data without changing its case number', function () {
    $manager = userWithRole('manager');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $originalNumber = $caseFile->case_no;

    $this->actingAs($manager)->put(route('case-files.update', $caseFile), [
        'case_type_id' => $caseFile->case_type_id,
        'title' => 'Güncellenen dosya başlığı',
        'priority' => 'urgent',
        'opened_at' => '2026-09-18',
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect(route('case-files.show', $caseFile));

    expect($caseFile->fresh()->title)->toBe('Güncellenen dosya başlığı');
    expect($caseFile->fresh()->case_no)->toBe($originalNumber);
});

it('allows an existing case to retain a deactivated case type', function () {
    $manager = userWithRole('manager');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $caseFile->caseType->update(['is_active' => false]);

    $this->actingAs($manager)->put(route('case-files.update', $caseFile), [
        'case_type_id' => $caseFile->case_type_id,
        'title' => 'Pasif türü koruyan dosya',
        'priority' => 'normal',
        'opened_at' => '2026-09-19',
        'lock_version' => $caseFile->lock_version,
    ])->assertRedirect();

    expect($caseFile->fresh()->case_type_id)->toBe($caseFile->case_type_id);
});
