<?php

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\CaseFileParty;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\Party;
use App\Models\PartyIdentifier;
use Illuminate\Support\Facades\DB;

it('lets managers create clients with encrypted identifiers', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)->post(route('clients.store'), [
        'type' => 'company',
        'company_name' => 'Tepenet Teknoloji AŞ',
        'phone' => '0332 000 00 00',
        'email' => 'hukuk@tepenet.test',
        'identifier_type' => 'tax_number',
        'identifier_value' => '1234567890',
    ])->assertRedirect();

    $client = Client::query()->with('party.identifiers')->firstOrFail();
    $identifier = $client->party->identifiers->firstOrFail();
    expect($client->party->company_name)->toBe('Tepenet Teknoloji AŞ');
    expect($identifier->value)->toBe('1234567890');
    expect(DB::table('party_identifiers')->where('id', $identifier->id)->value('value'))->not->toContain('1234567890');
});

it('renders client creation and edit forms', function () {
    $manager = userWithRole('manager');
    $client = Client::factory()->create(['created_by' => $manager]);

    $this->actingAs($manager)->get(route('clients.create'))->assertOk()->assertSee('Yeni Müvekkil Oluştur');
    $this->actingAs($manager)->get(route('clients.edit', $client))->assertOk()->assertSee('Müvekkili Düzenle');
});

it('lets managers delete unlinked clients and records the deletion', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $client = Client::factory()->create(['created_by' => $manager]);
    $partyId = $client->party_id;

    $this->actingAs($manager)->get(route('clients.index'))->assertSee('Müvekkili Sil');
    $this->actingAs($manager)->get(route('clients.show', $client))->assertSee('Silmeyi Onayla');
    $this->actingAs($lawyer)->delete(route('clients.destroy', $client))->assertForbidden();
    $this->actingAs($manager)->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'));

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    $this->assertSoftDeleted('parties', ['id' => $partyId]);
    expect(AuditLog::query()->where('action', AuditAction::ClientDeleted)->exists())->toBeTrue();
});

it('lets the responsible lawyer delete a client after its relationship ends while preserving the case party', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $client = Client::factory()->create(['created_by' => $manager]);
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $caseFileParty = CaseFileParty::factory()->create([
        'case_file_id' => $caseFile,
        'party_id' => $client->party_id,
        'added_by' => $manager,
    ]);
    $partyId = $client->party_id;

    $this->actingAs($otherLawyer)->delete(route('clients.destroy', $client))->assertForbidden();
    $this->actingAs($lawyer)->delete(route('clients.destroy', $client))->assertSessionHasErrors('client');
    $caseFileParty->forceFill(['left_at' => now()])->save();
    $this->actingAs($lawyer)->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'));

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    $this->assertDatabaseHas('parties', ['id' => $partyId, 'deleted_at' => null]);
    $this->assertModelExists($caseFileParty);
});

it('does not delete clients with communication history', function () {
    $manager = userWithRole('manager');
    $client = Client::factory()->create(['created_by' => $manager]);
    ClientCommunication::factory()->create(['client_id' => $client, 'user_id' => $manager]);

    $this->actingAs($manager)->delete(route('clients.destroy', $client))
        ->assertSessionHasErrors('client');

    $this->assertModelExists($client);
    expect($client->party()->withTrashed()->firstOrFail()->trashed())->toBeFalse();
});

it('does not let lawyers submit sensitive client identifiers', function () {
    $lawyer = userWithRole('lawyer');

    $this->actingAs($lawyer)->post(route('clients.store'), [
        'type' => 'individual',
        'name' => 'Ayşe',
        'surname' => 'Yılmaz',
        'identifier_type' => 'tckn',
        'identifier_value' => '12345678901',
    ])->assertSessionHasErrors(['identifier_type', 'identifier_value']);

    expect(Client::query()->count())->toBe(0);
    expect(PartyIdentifier::query()->count())->toBe(0);
});

it('does not flash sensitive identifiers after validation errors', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)->post(route('clients.store'), [
        'type' => 'company',
        'identifier_type' => 'tax_number',
        'identifier_value' => '1234567890',
    ])->assertSessionHasErrors('company_name')->assertSessionMissing('_old_input.identifier_value');
});

it('updates a client without requiring the existing identifier value again', function () {
    $manager = userWithRole('manager');
    $client = Client::factory()->create(['created_by' => $manager]);
    PartyIdentifier::factory()->create([
        'party_id' => $client->party_id,
        'created_by' => $manager,
        'type' => 'tax_number',
        'value' => '1234567890',
    ]);
    $client->refresh();

    $this->actingAs($manager)->put(route('clients.update', $client), [
        'type' => 'individual',
        'name' => 'Güncel',
        'surname' => 'Müvekkil',
        'status' => 'active',
        'identifier_type' => 'tax_number',
        'identifier_value' => '',
        'lock_version' => $client->lock_version,
    ])->assertRedirect(route('clients.show', $client));

    expect($client->fresh()->party->display_name)->toBe('Güncel Müvekkil');
    expect($client->party->identifiers()->first()->value)->toBe('1234567890');
});

it('rejects duplicate identifiers and stale client updates', function () {
    $manager = userWithRole('manager');
    $existingIdentifier = PartyIdentifier::factory()->create([
        'created_by' => $manager,
        'type' => 'tax_number',
        'value' => '1234567890',
    ]);

    $this->actingAs($manager)->post(route('clients.store'), [
        'type' => 'company',
        'company_name' => 'Mükerrer Kimlik AŞ',
        'identifier_type' => 'tax_number',
        'identifier_value' => '123 456 7890',
    ])->assertSessionHasErrors('identifier_value');

    $client = Client::factory()->create(['created_by' => $manager]);
    $staleVersion = $client->refresh()->lock_version;
    $client->forceFill(['lock_version' => $staleVersion + 1])->save();
    $this->actingAs($manager)->put(route('clients.update', $client), [
        'type' => $client->party->type->value,
        'name' => $client->party->name,
        'surname' => $client->party->surname,
        'company_name' => $client->party->company_name,
        'status' => 'active',
        'lock_version' => $staleVersion,
    ])->assertSessionHasErrors('lock_version');

    expect($existingIdentifier->exists)->toBeTrue();
});

it('scopes clients to their creator or assigned legal cases', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');

    $this->actingAs($lawyer)->post(route('clients.store'), [
        'type' => 'individual',
        'name' => 'Mehmet',
        'surname' => 'Kaya',
    ])->assertRedirect();
    $client = Client::query()->firstOrFail();

    $this->actingAs($lawyer)->get(route('clients.show', $client))->assertSee('Mehmet Kaya');
    $this->actingAs($otherLawyer)->get(route('clients.show', $client))->assertForbidden();
    $this->actingAs($manager)->get(route('clients.show', $client))->assertSee('Mehmet Kaya');
});

it('lets only the lead lawyer edit a client linked to a legal case', function () {
    $manager = userWithRole('manager');
    $leadLawyer = userWithRole('lawyer');
    $assignedLawyer = userWithRole('lawyer');
    $creator = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$leadLawyer, $assignedLawyer]);
    $party = Party::factory()->create(['created_by' => $creator, 'name' => 'Eski']);
    $client = Client::factory()->create(['party_id' => $party, 'created_by' => $creator]);
    CaseFileParty::factory()->create(['case_file_id' => $caseFile, 'party_id' => $party, 'added_by' => $manager]);
    $client->refresh();

    expect($manager->can('update', $client))->toBeTrue()
        ->and($leadLawyer->can('update', $client))->toBeTrue()
        ->and($leadLawyer->can('update', $party))->toBeTrue()
        ->and($assignedLawyer->can('view', $client))->toBeTrue()
        ->and($assignedLawyer->can('update', $client))->toBeFalse()
        ->and($assignedLawyer->can('update', $party))->toBeFalse()
        ->and($creator->can('update', $client))->toBeFalse();

    $this->actingAs($assignedLawyer)->get(route('clients.edit', $client))->assertForbidden();
    $this->actingAs($assignedLawyer)->put(route('clients.update', $client), [
        'type' => 'individual',
        'name' => 'Yetkisiz',
        'surname' => $party->surname,
        'status' => 'active',
        'lock_version' => $client->lock_version,
    ])->assertForbidden();
    expect($party->fresh()->name)->toBe('Eski');

    $this->actingAs($leadLawyer)->put(route('clients.update', $client), [
        'type' => 'individual',
        'name' => 'Güncel',
        'surname' => $party->surname,
        'status' => 'active',
        'lock_version' => $client->lock_version,
    ])->assertRedirect(route('clients.show', $client));
    expect($party->fresh()->name)->toBe('Güncel');

    $caseFile->assignments()->where('lawyer_id', $leadLawyer->id)->firstOrFail()
        ->forceFill(['ended_at' => now()])->save();
    expect($leadLawyer->can('update', $client))->toBeFalse();
});

it('requires the same lead lawyer on every legal case sharing a client', function () {
    $manager = userWithRole('manager');
    $firstLead = userWithRole('lawyer');
    $secondLead = userWithRole('lawyer');
    $client = Client::factory()->create(['created_by' => $manager]);
    $firstCase = legalCaseFile($manager, [$firstLead]);
    $secondCase = legalCaseFile($manager, [$firstLead]);
    $thirdCase = legalCaseFile($manager, [$secondLead]);
    CaseFileParty::factory()->create(['case_file_id' => $firstCase, 'party_id' => $client->party_id, 'added_by' => $manager]);

    expect($firstLead->can('update', $client))->toBeTrue();

    CaseFileParty::factory()->create(['case_file_id' => $secondCase, 'party_id' => $client->party_id, 'added_by' => $manager]);

    expect($firstLead->can('update', $client))->toBeTrue();

    CaseFileParty::factory()->create(['case_file_id' => $thirdCase, 'party_id' => $client->party_id, 'added_by' => $manager]);

    expect($firstLead->can('update', $client))->toBeFalse()
        ->and($secondLead->can('update', $client))->toBeFalse()
        ->and($manager->can('update', $client))->toBeTrue();
});

it('keeps an unlinked client editable by its creator', function () {
    $creator = userWithRole('lawyer');
    $party = Party::factory()->create(['created_by' => $creator]);
    $client = Client::factory()->create(['party_id' => $party, 'created_by' => $creator]);

    expect($creator->can('update', $client))->toBeTrue();
});

it('does not render sensitive identifiers to lawyers', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $client = Client::factory()->create(['created_by' => $manager]);
    PartyIdentifier::factory()->create([
        'party_id' => $client->party_id,
        'created_by' => $manager,
        'value' => '12345678901',
    ]);
    $caseFile->parties()->attach($client->party_id, [
        'role' => 'client',
        'side' => 'own',
        'is_primary' => true,
        'added_by' => $manager->id,
        'joined_at' => now(),
    ]);

    $this->actingAs($lawyer)->get(route('clients.show', $client))
        ->assertOk()
        ->assertDontSee('12345678901');
});

it('forbids employees from client management', function () {
    $employee = userWithRole('employee');

    $this->actingAs($employee)->get(route('clients.index'))->assertForbidden();
    $this->actingAs($employee)->get(route('clients.create'))->assertForbidden();
});
