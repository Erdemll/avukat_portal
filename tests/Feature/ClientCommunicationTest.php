<?php

use App\CommunicationType;
use App\Models\CaseFileParty;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\Party;
use App\Services\LawyerRetirementService;

it('records client communications against matching visible case files', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $client = Client::factory()->create(['created_by' => $manager]);
    CaseFileParty::factory()->create(['case_file_id' => $caseFile, 'party_id' => $client->party_id, 'added_by' => $manager]);

    $this->actingAs($lawyer)->post(route('client-communications.store'), [
        'client_id' => $client->id,
        'case_file_id' => $caseFile->id,
        'type' => 'phone',
        'subject' => 'Bilirkişi raporu bilgilendirmesi',
        'description' => 'Müvekkile raporun sonucu açıklandı.',
        'communication_at' => '2026-09-19 14:32:00',
    ])->assertRedirect();

    $communication = ClientCommunication::query()->firstOrFail();
    expect($communication->type)->toBe(CommunicationType::Phone);
    expect($communication->user_id)->toBe($lawyer->id);
    $this->actingAs($lawyer)->get(route('client-communications.index'))->assertOk()->assertSee('Bilirkişi raporu');
});

it('rejects mismatched clients cases and unrelated lawyers', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $client = Client::factory()->create(['created_by' => $lawyer]);
    $payload = ['client_id' => $client->id, 'case_file_id' => $caseFile->id, 'type' => 'email', 'subject' => 'Konu', 'description' => 'Açıklama', 'communication_at' => now()->toDateTimeString()];

    $this->actingAs($lawyer)->post(route('client-communications.store'), $payload)->assertSessionHasErrors('case_file_id');
    $this->actingAs(userWithRole('lawyer'))->post(route('client-communications.store'), [...$payload, 'case_file_id' => null])->assertSessionHasErrors('client_id');
});

it('prevents stale communication updates', function () {
    $lawyer = userWithRole('lawyer');
    $client = Client::factory()->create(['created_by' => $lawyer]);
    $communication = ClientCommunication::factory()->create(['client_id' => $client, 'user_id' => $lawyer])->refresh();

    $this->actingAs($lawyer)->put(route('client-communications.update', $communication), [
        'client_id' => $client->id,
        'type' => 'phone',
        'subject' => 'Güncelleme',
        'description' => 'Not',
        'communication_at' => now()->toDateTimeString(),
        'lock_version' => $communication->lock_version + 1,
    ])->assertSessionHasErrors('lock_version');
});

it('shows a successor past unlinked client communications without changing the author', function () {
    $manager = userWithRole('manager');
    $previousLawyer = userWithRole('lawyer');
    $replacement = userWithRole('lawyer');
    $party = Party::factory()->create(['created_by' => $previousLawyer]);
    $client = Client::factory()->create(['party_id' => $party, 'created_by' => $previousLawyer, 'responsible_lawyer_id' => $previousLawyer]);
    $communication = ClientCommunication::factory()->create(['client_id' => $client, 'user_id' => $previousLawyer, 'case_file_id' => null, 'subject' => 'Eski görüşme']);
    app(LawyerRetirementService::class)->retire($previousLawyer, $replacement, $manager);

    $this->actingAs($replacement)->get(route('client-communications.show', $communication))->assertOk()->assertSee('Eski görüşme');
    expect($replacement->can('update', $communication))->toBeFalse();
    expect($communication->fresh()->user_id)->toBe($previousLawyer->id);
});
