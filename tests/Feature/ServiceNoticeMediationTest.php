<?php

use App\AuditAction;
use App\MediationStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Mediation;
use App\Models\ServiceNotice;
use App\Notifications\ServiceNoticeCreatedNotification;
use Illuminate\Support\Facades\Notification;

it('creates case scoped service notices with linked documents and notifications', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $document = Document::factory()->create(['event_id' => null, 'case_file_id' => $caseFile, 'uploaded_by' => $lawyer]);

    $this->actingAs($lawyer)->post(route('service-notices.store'), [
        'case_file_id' => $caseFile->id,
        'type' => 'court',
        'sender' => 'Konya 3. İş Mahkemesi',
        'recipient' => 'Tepenet',
        'service_date' => '2026-09-19',
        'document_id' => $document->id,
    ])->assertRedirect();

    $notice = ServiceNotice::query()->firstOrFail();
    expect($notice->service_date->toDateString())->toBe('2026-09-19');
    expect(AuditLog::query()->where('action', AuditAction::ServiceNoticeCreated)->where('case_file_id', $caseFile->id)->exists())->toBeTrue();
    Notification::assertSentTo($manager, ServiceNoticeCreatedNotification::class);
});

it('rejects documents and users outside the service notice case scope', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $otherCase = legalCaseFile($manager, [userWithRole('lawyer')]);
    $document = Document::factory()->create(['event_id' => null, 'case_file_id' => $otherCase, 'uploaded_by' => $manager]);
    $payload = ['case_file_id' => $caseFile->id, 'type' => 'court', 'service_date' => '2026-09-19', 'document_id' => $document->id];

    $this->actingAs($lawyer)->post(route('service-notices.store'), $payload)->assertSessionHasErrors('document_id');
    $this->actingAs(userWithRole('lawyer'))->post(route('service-notices.store'), [...$payload, 'document_id' => null])->assertSessionHasErrors('case_file_id');
});

it('tracks mediation outcomes with required completion details and optimistic locking', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->post(route('mediations.store'), [
        'case_file_id' => $caseFile->id,
        'mediation_file_no' => '2026/1234',
        'status' => 'agreement',
    ])->assertSessionHasErrors(['completion_date', 'result']);
    $this->actingAs($lawyer)->post(route('mediations.store'), [
        'case_file_id' => $caseFile->id,
        'mediation_file_no' => '2026/1234',
        'application_date' => '2026-09-01',
        'completion_date' => '2026-09-19',
        'status' => 'agreement',
        'result' => 'Taraflar anlaşmaya vardı.',
    ])->assertRedirect();

    $mediation = Mediation::query()->firstOrFail()->refresh();
    expect($mediation->status)->toBe(MediationStatus::Agreement);
    $this->actingAs($lawyer)->put(route('mediations.update', $mediation), [
        'case_file_id' => $caseFile->id,
        'status' => 'ongoing',
        'lock_version' => $mediation->lock_version + 1,
    ])->assertSessionHasErrors('lock_version');
});

it('renders service notice and mediation screens for assigned lawyers', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->get(route('service-notices.create', ['case_file' => $caseFile->id]))->assertOk();
    $this->actingAs($lawyer)->get(route('mediations.index'))->assertOk();
});
