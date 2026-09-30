<?php

use App\AuditAction;
use App\MediationStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Mediation;
use App\Models\ServiceNotice;
use App\Notifications\ServiceNoticeCreatedNotification;
use Illuminate\Support\Facades\Gate;
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

it('lets a lawyer remove their own notice while retaining its document and audit history', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $document = Document::factory()->create(['event_id' => null, 'case_file_id' => $caseFile, 'uploaded_by' => $lawyer]);
    $notice = ServiceNotice::factory()->create(['case_file_id' => $caseFile, 'document_id' => $document, 'created_by' => $lawyer])->refresh();

    $this->actingAs($lawyer)->get(route('service-notices.edit', $notice))->assertSee('Tebligatı Sil');
    $this->actingAs($lawyer)->delete(route('service-notices.destroy', $notice), ['lock_version' => $notice->lock_version])
        ->assertRedirect(route('service-notices.index'));

    $this->assertSoftDeleted($notice);
    $this->assertModelExists($document);
    expect(ServiceNotice::query()->whereKey($notice->id)->exists())->toBeFalse();
    expect(AuditLog::query()->where('action', AuditAction::ServiceNoticeDeleted)->where('auditable_id', $notice->id)->where('user_id', $lawyer->id)->exists())->toBeTrue();
    $this->actingAs($lawyer)->get(route('service-notices.index'))->assertDontSee($notice->sender);
    $this->actingAs($lawyer)->delete(route('service-notices.destroy', $notice), ['lock_version' => $notice->lock_version])->assertNotFound();
});

it('lets an assistant remove their own notice only within a case they created', function () {
    $assistant = userWithRole('assistant');
    $ownCase = legalCaseFile($assistant);
    $foreignCase = legalCaseFile(userWithRole('manager'));
    $ownNotice = ServiceNotice::factory()->create(['case_file_id' => $ownCase, 'created_by' => $assistant])->refresh();
    $foreignNotice = ServiceNotice::factory()->create(['case_file_id' => $foreignCase, 'created_by' => $assistant])->refresh();

    expect(Gate::forUser($assistant)->allows('delete', $ownNotice))->toBeTrue();
    expect(Gate::forUser($assistant)->allows('delete', $foreignNotice))->toBeFalse();
    $this->actingAs($assistant)->delete(route('service-notices.destroy', $foreignNotice), ['lock_version' => $foreignNotice->lock_version])->assertForbidden();
    $this->actingAs($assistant)->delete(route('service-notices.destroy', $ownNotice), ['lock_version' => $ownNotice->lock_version])->assertRedirect(route('service-notices.index'));

    $this->assertSoftDeleted($ownNotice);
    $this->assertModelExists($foreignNotice);
});

it('rejects deletion by another author or a role without deletion rights', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer, $otherLawyer]);
    $notice = ServiceNotice::factory()->create(['case_file_id' => $caseFile, 'created_by' => $lawyer])->refresh();

    foreach ([$manager, $otherLawyer, userWithRole('employee')] as $user) {
        expect(Gate::forUser($user)->allows('delete', $notice))->toBeFalse();
        $this->actingAs($user)->delete(route('service-notices.destroy', $notice), ['lock_version' => $notice->lock_version])->assertForbidden();
    }

    $this->assertModelExists($notice);
    expect(AuditLog::query()->where('action', AuditAction::ServiceNoticeDeleted)->exists())->toBeFalse();
});

it('requires the current notice version before deletion', function () {
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile(userWithRole('manager'), [$lawyer]);
    $notice = ServiceNotice::factory()->create(['case_file_id' => $caseFile, 'created_by' => $lawyer])->refresh();

    $this->actingAs($lawyer)->delete(route('service-notices.destroy', $notice), [])->assertSessionHasErrors('lock_version');
    $this->actingAs($lawyer)->delete(route('service-notices.destroy', $notice), ['lock_version' => $notice->lock_version + 1])->assertSessionHasErrors('lock_version');

    $this->assertModelExists($notice);
    expect(AuditLog::query()->where('action', AuditAction::ServiceNoticeDeleted)->exists())->toBeFalse();
});
