<?php

use App\CaseAssignmentRole;
use App\CaseEventRelationType;
use App\CaseFilePartyRole;
use App\CasePartySide;
use App\Models\CaseFile;
use App\Models\CaseFileAssignment;
use App\Models\CaseFileEvent;
use App\Models\CaseFileParty;
use App\Models\CaseProceeding;
use App\Models\Client;
use App\Models\Party;
use App\Models\PartyIdentifier;
use App\Services\CaseFileNumberService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('encrypts party identifiers and keeps deterministic lookup hashes', function () {
    $manager = userWithRole('manager');
    $party = Party::factory()->create(['created_by' => $manager]);
    $identifier = PartyIdentifier::factory()->create([
        'party_id' => $party,
        'created_by' => $manager,
        'value' => '123 456 789 01',
    ]);

    $storedValue = DB::table('party_identifiers')->where('id', $identifier->id)->value('value');

    expect($storedValue)->not->toContain('123 456 789 01');
    expect($identifier->fresh()->value)->toBe('123 456 789 01');
    expect($identifier->value_hash)->toBe(PartyIdentifier::hashValue('12345678901'));
    expect($identifier->fresh()->country_code)->toBe('TR');
    expect($identifier->toArray())->not->toHaveKeys(['value', 'value_hash']);
    expect($manager->can('viewIdentifiers', $party))->toBeTrue();
    expect(userWithRole('lawyer')->can('viewIdentifiers', $party))->toBeFalse();
});

it('normalizes identifier country codes before enforcing uniqueness', function () {
    $manager = userWithRole('manager');
    $party = Party::factory()->create(['created_by' => $manager]);
    PartyIdentifier::factory()->create([
        'party_id' => $party,
        'created_by' => $manager,
        'value' => '12345678901',
        'country_code' => 'tr',
    ]);

    expect(fn () => PartyIdentifier::factory()->create([
        'party_id' => Party::factory()->create(['created_by' => $manager]),
        'created_by' => $manager,
        'value' => '123 456 789 01',
        'country_code' => 'TR',
    ]))->toThrow(QueryException::class);
});

it('allocates immutable yearly legal case numbers in sequence', function () {
    $numbers = app(CaseFileNumberService::class);

    expect($numbers->next(2026, 'TPN'))->toBe('TPN-2026-000001')
        ->and($numbers->next(2026, 'TPN'))->toBe('TPN-2026-000002')
        ->and($numbers->next(2027, 'TPN'))->toBe('TPN-2027-000001');
});

it('prevents a persisted legal case number from being changed', function () {
    $caseFile = CaseFile::factory()->create();

    expect(fn () => $caseFile->forceFill(['case_no' => 'TPN-2026-999999'])->save())
        ->toThrow(LogicException::class, 'Hukuki dosya numarası değiştirilemez.');
});

it('supports multiple active lawyers on one legal case file', function () {
    $manager = userWithRole('manager');
    $leadLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $caseFile = CaseFile::factory()->create(['created_by' => $manager]);

    CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $leadLawyer,
        'role' => CaseAssignmentRole::Lead,
        'assigned_by' => $manager,
    ]);
    CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $secondLawyer,
        'role' => CaseAssignmentRole::Lawyer,
        'assigned_by' => $manager,
    ]);

    expect($caseFile->activeLawyers()->pluck('users.id')->all())
        ->toEqualCanonicalizing([$leadLawyer->id, $secondLawyer->id]);
    expect($caseFile->activeLawyers()->first()->pivot->role)->toBeInstanceOf(CaseAssignmentRole::class);
    expect(CaseFile::query()->visibleTo($leadLawyer)->pluck('id')->all())->toBe([$caseFile->id]);
    expect(CaseFile::query()->visibleTo($secondLawyer)->pluck('id')->all())->toBe([$caseFile->id]);
    expect(CaseFile::query()->visibleTo(userWithRole('employee'))->exists())->toBeFalse();
    expect(CaseFile::query()->visibleTo(userWithRole('lawyer'))->exists())->toBeFalse();
    expect(CaseFile::query()->visibleTo($manager)->pluck('id')->all())->toBe([$caseFile->id]);
});

it('prevents duplicate active lawyer assignments and multiple active leads', function () {
    $manager = userWithRole('manager');
    $leadLawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $caseFile = CaseFile::factory()->create(['created_by' => $manager]);
    CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $leadLawyer,
        'role' => CaseAssignmentRole::Lead,
        'assigned_by' => $manager,
    ]);

    expect(fn () => CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $leadLawyer,
        'assigned_by' => $manager,
    ]))->toThrow(QueryException::class);
    expect(fn () => CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $otherLawyer,
        'role' => CaseAssignmentRole::Lead,
        'assigned_by' => $manager,
    ]))->toThrow(QueryException::class);
});

it('removes ended assignments from lawyer visibility without losing history', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = CaseFile::factory()->create(['created_by' => $manager]);
    CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $lawyer,
        'assigned_by' => $manager,
        'ended_at' => now(),
        'ended_by' => $manager,
    ]);

    expect(CaseFile::query()->visibleTo($lawyer)->exists())->toBeFalse();
    expect($caseFile->assignments()->count())->toBe(1);
});

it('requires a replacement before retiring a lawyer with an active legal case assignment', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = CaseFile::factory()->create(['created_by' => $manager]);
    CaseFileAssignment::factory()->create([
        'case_file_id' => $caseFile,
        'lawyer_id' => $lawyer,
        'assigned_by' => $manager,
    ]);

    $this->actingAs($manager)->post(route('admin.users.deactivate', $lawyer))->assertSessionHasErrors('replacement_lawyer_id');
    expect($lawyer->fresh()->is_active)->toBeTrue();
    expect($caseFile->activeLawyers()->first()->is($lawyer))->toBeTrue();
});

it('links intake events parties clients and external proceedings to a legal case file', function () {
    $manager = userWithRole('manager');
    $caseFile = CaseFile::factory()->create(['created_by' => $manager]);
    $event = legalEvent(userWithRole('employee'), userWithRole('lawyer'));
    $party = Party::factory()->company()->create(['created_by' => $manager]);
    Client::factory()->create(['party_id' => $party, 'created_by' => $manager]);
    CaseFileEvent::factory()->create([
        'case_file_id' => $caseFile,
        'event_id' => $event,
        'relation_type' => CaseEventRelationType::Origin,
        'linked_by' => $manager,
    ]);
    CaseFileParty::factory()->create([
        'case_file_id' => $caseFile,
        'party_id' => $party,
        'role' => CaseFilePartyRole::Client,
        'side' => CasePartySide::Own,
        'added_by' => $manager,
    ]);
    CaseProceeding::factory()->create(['case_file_id' => $caseFile]);

    expect($caseFile->events()->first()->is($event))->toBeTrue();
    expect($caseFile->parties()->first()->is($party))->toBeTrue();
    expect($party->client)->not->toBeNull();
    expect($caseFile->proceedings()->count())->toBe(1);
});
