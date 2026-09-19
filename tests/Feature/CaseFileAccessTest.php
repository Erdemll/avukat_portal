<?php

use App\Models\CaseFile;
use App\Models\CaseFileEvent;
use App\Models\CaseFileParty;
use App\Models\Client;

it('scopes legal case pages to managers and assigned lawyers', function () {
    $manager = userWithRole('manager');
    $assignedLawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $employee = userWithRole('employee');
    $caseFile = legalCaseFile($manager, [$assignedLawyer]);

    $this->actingAs($manager)->get(route('case-files.show', $caseFile))->assertOk();
    $this->actingAs($assignedLawyer)->get(route('case-files.show', $caseFile))->assertOk();
    $this->actingAs($otherLawyer)->get(route('case-files.show', $caseFile))->assertForbidden();
    $this->actingAs($employee)->get(route('case-files.index'))->assertForbidden();
    $this->actingAs($employee)->get(route('case-files.show', $caseFile))->assertForbidden();
});

it('renders case creation edit and request conversion forms for authorized users', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $event = legalEvent(userWithRole('employee'), $lawyer);

    $this->actingAs($manager)->get(route('case-files.create'))->assertOk()->assertSee('Yeni Hukuki Dosya Oluştur');
    $this->actingAs($lawyer)->get(route('case-files.edit', $caseFile))->assertOk()->assertSee($caseFile->case_no);
    $this->actingAs($lawyer)->get(route('events.case-file.create', $event))->assertOk()->assertSee($event->event_no);
});

it('does not let assigned lawyers manage the legal team', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->put(route('case-files.assignments.update', $caseFile), [
        'lawyer_ids' => [$lawyer->id],
        'lead_lawyer_id' => $lawyer->id,
        'lock_version' => $caseFile->lock_version,
    ])->assertForbidden();
});

it('shows only assigned legal cases in lawyer filters and search', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $visible = legalCaseFile($manager, [$lawyer]);
    $visible->forceFill(['title' => 'Görünen iş davası'])->save();
    $hidden = legalCaseFile($manager, [userWithRole('lawyer')]);
    $hidden->forceFill(['title' => 'Gizli icra dosyası'])->save();

    $this->actingAs($lawyer)->get(route('case-files.index', ['search' => 'Görünen']))
        ->assertSee('Görünen iş davası')
        ->assertDontSee('Gizli icra dosyası');
});

it('lets assigned lawyers add and end case party relationships', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->post(route('case-files.parties.store', $caseFile), [
        'type' => 'company',
        'company_name' => 'Karşı Taraf AŞ',
        'role' => 'defendant',
        'side' => 'opposing',
    ])->assertRedirect();

    $caseParty = CaseFileParty::query()->firstOrFail();
    expect($caseParty->party->company_name)->toBe('Karşı Taraf AŞ');
    $this->actingAs($lawyer)->delete(route('case-files.parties.destroy', [$caseFile, $caseParty]))->assertRedirect();
    expect($caseParty->fresh()->left_at)->not->toBeNull();
});

it('revokes client visibility when the case party relationship ends', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $client = Client::factory()->create(['created_by' => $manager]);
    $caseParty = CaseFileParty::factory()->create([
        'case_file_id' => $caseFile,
        'party_id' => $client->party_id,
        'added_by' => $manager,
    ]);

    $this->actingAs($lawyer)->get(route('clients.show', $client))->assertOk();
    $this->actingAs($lawyer)->delete(route('case-files.parties.destroy', [$caseFile, $caseParty]))->assertRedirect();
    $this->actingAs($lawyer)->get(route('clients.show', $client))->assertForbidden();
});

it('returns not found when ending a party relationship through another case', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $firstCase = legalCaseFile($manager, [$lawyer]);
    $secondCase = legalCaseFile($manager, [$lawyer]);
    $caseParty = CaseFileParty::factory()->create(['case_file_id' => $firstCase, 'added_by' => $manager]);

    $this->actingAs($lawyer)->delete(route('case-files.parties.destroy', [$secondCase, $caseParty]))->assertNotFound();
    expect($caseParty->fresh()->left_at)->toBeNull();
});

it('does not expose an inaccessible source request through a legal case', function () {
    $manager = userWithRole('manager');
    $caseLawyer = userWithRole('lawyer');
    $eventLawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$caseLawyer]);
    $event = legalEvent(userWithRole('employee'), $eventLawyer);
    $event->forceFill(['title' => 'Gizli kaynak talep'])->save();
    CaseFileEvent::factory()->create([
        'case_file_id' => $caseFile,
        'event_id' => $event,
        'linked_by' => $manager,
    ]);

    $this->actingAs($caseLawyer)->get(route('case-files.show', $caseFile))
        ->assertOk()
        ->assertDontSee('Gizli kaynak talep');
});

it('forbids employees from converting legal requests', function () {
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));

    $this->actingAs($employee)->get(route('events.case-file.create', $event))->assertForbidden();
    expect(CaseFile::query()->count())->toBe(0);
});
