<?php

use App\AuditAction;
use App\EventStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\EventType;
use App\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

it('authenticates lawyers on the web guard with their registry number', function () {
    $lawyer = userWithRole('lawyer');
    $lawyer->forceFill([
        'tc_kimlik_no' => '12345678901',
        'password' => 'StrongPassword123',
    ])->save();

    $this->post(route('login.store'), [
        'login_type' => 'lawyer',
        'sicil_no' => '12345678901',
        'password' => 'StrongPassword123',
    ])->assertRedirect(route('case-files.index'))->assertSessionDoesntHaveErrors();

    $this->assertAuthenticatedAs($lawyer);
    $this->get(route('events.index'))->assertOk();
});

it('reassigns events and records the assignment change', function () {
    $manager = userWithRole('manager');
    $event = legalEvent(userWithRole('employee'), userWithRole('lawyer'));
    $newLawyer = userWithRole('lawyer');

    $this->actingAs($manager)->patch(route('events.lawyer.update', $event), [
        'assigned_lawyer_id' => $newLawyer->id,
    ])->assertRedirect();

    expect($event->fresh()->assigned_lawyer_id)->toBe($newLawyer->id);
    $this->assertDatabaseHas('audit_logs', [
        'event_id' => $event->id,
        'action' => AuditAction::EventReassigned->value,
    ]);
});

it('activates and deactivates event types without losing the audit record', function () {
    $manager = userWithRole('manager');
    $eventType = EventType::factory()->create(['is_active' => true]);

    $this->actingAs($manager)->post(route('admin.event-types.deactivate', $eventType))->assertRedirect();
    expect($eventType->fresh()->is_active)->toBeFalse();

    $this->actingAs($manager)->post(route('admin.event-types.activate', $eventType))->assertRedirect();
    expect($eventType->fresh()->is_active)->toBeTrue();
    expect(AuditLog::query()->where('action', AuditAction::EventTypeActivated)->exists())->toBeTrue();
});

it('rolls back a status change when its mandatory audit record fails', function () {
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $this->mock(AuditService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('log')->once()->andThrow(new RuntimeException('audit unavailable'));
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($lawyer)->patch(route('events.status.update', $event), [
        'status' => EventStatus::Closed->value,
    ]))->toThrow(RuntimeException::class, 'audit unavailable');
    expect($event->fresh()->system_status)->toBe(EventStatus::Open);
});

it('removes partially uploaded files when mandatory auditing fails', function () {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));
    $this->mock(AuditService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('log')->once()->andThrow(new RuntimeException('audit unavailable'));
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($employee)->post(route('events.documents.store', $event), [
        'documents' => [UploadedFile::fake()->create('sozlesme.pdf', 20, 'application/pdf')],
    ]))->toThrow(RuntimeException::class, 'audit unavailable');
    expect(Document::query()->count())->toBe(0);
    expect(Storage::disk('legal_private')->allFiles())->toBe([]);
});

it('keeps audit records immutable through the application model', function () {
    $auditLog = AuditLog::factory()->create();

    expect(fn () => $auditLog->update(['description' => 'değiştirildi']))
        ->toThrow(LogicException::class, 'Audit kayıtları değiştirilemez.');
});
