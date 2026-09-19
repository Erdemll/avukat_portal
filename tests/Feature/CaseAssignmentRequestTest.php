<?php

use App\CaseAssignmentRequestStatus;
use App\CaseAssignmentRole;
use App\Models\CaseAssignmentRequest;
use App\Notifications\CaseAssignmentRequestCreatedNotification;
use App\Notifications\CaseAssignmentRequestDecidedNotification;
use Illuminate\Support\Facades\Notification;

it('atomically transfers a case to the approved target lawyer', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $leadLawyer = userWithRole('lawyer');
    $secondLawyer = userWithRole('lawyer');
    $targetLawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$leadLawyer, $secondLawyer]);

    $this->actingAs($leadLawyer)->post(route('assignment-requests.store'), [
        'case_file_id' => $caseFile->id,
        'type' => 'transfer',
        'requested_to' => $targetLawyer->id,
        'reason' => 'İş yoğunluğu nedeniyle devir.',
    ])->assertRedirect();

    $request = CaseAssignmentRequest::query()->firstOrFail();
    Notification::assertSentTo([$manager, $targetLawyer], CaseAssignmentRequestCreatedNotification::class);
    $this->actingAs($manager)->post(route('assignment-requests.review', $request), [
        'decision' => 'approved',
        'requested_to' => $targetLawyer->id,
        'decision_note' => 'Uygundur.',
    ])->assertRedirect();

    expect($request->fresh()->status)->toBe(CaseAssignmentRequestStatus::Approved);
    expect($caseFile->activeLawyers()->pluck('users.id')->all())->toEqualCanonicalizing([$secondLawyer->id, $targetLawyer->id]);
    expect($caseFile->activeLawyers()->wherePivot('role', CaseAssignmentRole::Lead->value)->first()->is($targetLawyer))->toBeTrue();
    Notification::assertSentTo($leadLawyer, CaseAssignmentRequestDecidedNotification::class);
});

it('adds a lawyer after an approved assignment claim without replacing the lead', function () {
    $manager = userWithRole('manager');
    $leadLawyer = userWithRole('lawyer');
    $claimant = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$leadLawyer]);

    $this->actingAs($claimant)->post(route('assignment-requests.store'), [
        'case_no' => $caseFile->case_no,
        'type' => 'claim',
        'reason' => 'Dosyanın uzmanlık alanımda olması.',
    ])->assertRedirect();
    $request = CaseAssignmentRequest::query()->firstOrFail();
    $this->actingAs($manager)->post(route('assignment-requests.review', $request), ['decision' => 'approved'])->assertRedirect();

    expect($caseFile->activeLawyers()->pluck('users.id')->all())->toEqualCanonicalizing([$leadLawyer->id, $claimant->id]);
    expect($caseFile->activeLawyers()->wherePivot('role', 'lead')->first()->is($leadLawyer))->toBeTrue();
});

it('rejects duplicate invalid and unauthorized assignment requests', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $target = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $payload = ['case_file_id' => $caseFile->id, 'type' => 'transfer', 'requested_to' => $target->id, 'reason' => 'Devir'];

    $this->actingAs($lawyer)->post(route('assignment-requests.store'), $payload)->assertRedirect();
    $this->actingAs($lawyer)->post(route('assignment-requests.store'), $payload)->assertSessionHasErrors('case_file_id');
    $request = CaseAssignmentRequest::query()->firstOrFail();
    $this->actingAs($target)->post(route('assignment-requests.review', $request), ['decision' => 'approved'])->assertForbidden();
});

it('allows requesters to cancel pending requests but not decided requests', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $target = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $request = CaseAssignmentRequest::factory()->create(['case_file_id' => $caseFile, 'requested_by' => $lawyer, 'requested_to' => $target]);

    $this->actingAs($lawyer)->post(route('assignment-requests.cancel', $request))->assertRedirect();
    expect($request->fresh()->status)->toBe(CaseAssignmentRequestStatus::Cancelled);
    $this->actingAs($lawyer)->post(route('assignment-requests.cancel', $request))->assertForbidden();
});
