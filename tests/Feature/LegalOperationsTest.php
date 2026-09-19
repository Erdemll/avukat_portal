<?php

use App\DeadlineStatus;
use App\HearingStatus;
use App\LegalTaskStatus;
use App\Models\Deadline;
use App\Models\Hearing;
use App\Models\LegalTask;

it('creates hearings deadlines and tasks for assigned case lawyers', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->post(route('hearings.store'), [
        'case_file_id' => $caseFile->id,
        'lawyer_id' => $lawyer->id,
        'title' => 'Ön inceleme duruşması',
        'court' => 'Konya 3. İş Mahkemesi',
        'hearing_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'status' => 'scheduled',
    ])->assertRedirect();
    $this->actingAs($lawyer)->post(route('deadlines.store'), [
        'case_file_id' => $caseFile->id,
        'assigned_lawyer_id' => $lawyer->id,
        'title' => 'Cevap süresi',
        'starts_at' => now()->format('Y-m-d H:i:s'),
        'due_at' => now()->addDays(14)->format('Y-m-d H:i:s'),
        'status' => 'open',
    ])->assertRedirect();
    $this->actingAs($lawyer)->post(route('legal-tasks.store'), [
        'case_file_id' => $caseFile->id,
        'assigned_to' => $lawyer->id,
        'title' => 'Dilekçe hazırla',
        'priority' => 'high',
        'due_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'status' => 'pending',
    ])->assertRedirect();

    expect(Hearing::query()->first()->status)->toBe(HearingStatus::Scheduled);
    expect(Deadline::query()->first()->status)->toBe(DeadlineStatus::Open);
    expect(LegalTask::query()->first()->status)->toBe(LegalTaskStatus::Pending);
});

it('renders operation lists and forms for assigned lawyers', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $hearing = Hearing::factory()->create(['case_file_id' => $caseFile, 'lawyer_id' => $lawyer, 'created_by' => $lawyer]);
    $deadline = Deadline::factory()->create(['case_file_id' => $caseFile, 'assigned_lawyer_id' => $lawyer, 'created_by' => $lawyer]);
    $task = LegalTask::factory()->create(['case_file_id' => $caseFile, 'assigned_to' => $lawyer, 'created_by' => $lawyer]);

    $this->actingAs($lawyer)->get(route('hearings.index'))->assertOk();
    $this->actingAs($lawyer)->get(route('hearings.edit', $hearing))->assertOk()->assertSee($caseFile->case_no);
    $this->actingAs($lawyer)->get(route('deadlines.edit', $deadline))->assertOk()->assertSee('Hukuki Süreyi Düzenle');
    $this->actingAs($lawyer)->get(route('legal-tasks.edit', $task))->assertOk()->assertSee('Görevi Düzenle');
});

it('prevents unassigned lawyers from writing or reading another cases operations', function () {
    $manager = userWithRole('manager');
    $assigned = userWithRole('lawyer');
    $other = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$assigned]);
    $hearing = Hearing::factory()->create(['case_file_id' => $caseFile, 'lawyer_id' => $assigned, 'created_by' => $assigned]);

    $this->actingAs($other)->get(route('hearings.edit', $hearing))->assertForbidden();
    $this->actingAs($other)->post(route('deadlines.store'), [
        'case_file_id' => $caseFile->id,
        'title' => 'Yetkisiz süre',
        'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => 'open',
    ])->assertSessionHasErrors('case_file_id');
});

it('updates completion timestamps and rejects stale operation forms', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $deadline = Deadline::factory()->create(['case_file_id' => $caseFile, 'assigned_lawyer_id' => $lawyer, 'created_by' => $lawyer]);
    $deadline->refresh();

    $this->actingAs($lawyer)->put(route('deadlines.update', $deadline), [
        'case_file_id' => $caseFile->id,
        'assigned_lawyer_id' => $lawyer->id,
        'title' => $deadline->title,
        'starts_at' => $deadline->starts_at?->format('Y-m-d H:i:s'),
        'due_at' => $deadline->due_at->format('Y-m-d H:i:s'),
        'status' => 'completed',
        'lock_version' => $deadline->lock_version,
    ])->assertRedirect();

    expect($deadline->fresh()->completed_at)->not->toBeNull();
    $this->actingAs($lawyer)->put(route('deadlines.update', $deadline), [
        'case_file_id' => $caseFile->id,
        'assigned_lawyer_id' => $lawyer->id,
        'title' => $deadline->title,
        'due_at' => $deadline->due_at->format('Y-m-d H:i:s'),
        'status' => 'open',
        'lock_version' => 0,
    ])->assertSessionHasErrors('lock_version');
});

it('allows lawyers to create personal tasks only for themselves', function () {
    $lawyer = userWithRole('lawyer');
    $other = userWithRole('lawyer');

    $this->actingAs($lawyer)->post(route('legal-tasks.store'), [
        'assigned_to' => $other->id,
        'title' => 'Yetkisiz kişisel görev',
        'priority' => 'normal',
        'status' => 'pending',
    ])->assertSessionHasErrors('assigned_to');
    $this->actingAs($lawyer)->post(route('legal-tasks.store'), [
        'assigned_to' => $lawyer->id,
        'title' => 'Kişisel dosya kontrolü',
        'priority' => 'normal',
        'status' => 'pending',
    ])->assertRedirect();
    expect(LegalTask::query()->count())->toBe(1);
});

it('revokes case task access when the lawyers case assignment ends', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $task = LegalTask::factory()->create([
        'case_file_id' => $caseFile,
        'assigned_to' => $lawyer,
        'created_by' => $lawyer,
    ]);
    $assignment = $caseFile->assignments()->where('lawyer_id', $lawyer->id)->firstOrFail();
    $assignment->forceFill(['ended_at' => now(), 'ended_by' => $manager->id])->save();

    $this->actingAs($lawyer)->get(route('legal-tasks.edit', $task))->assertForbidden();
    $this->actingAs($lawyer)->put(route('legal-tasks.update', $task), [
        'assigned_to' => $lawyer->id,
        'title' => $task->title,
        'priority' => 'normal',
        'status' => 'pending',
        'lock_version' => $task->refresh()->lock_version,
    ])->assertForbidden();
});
