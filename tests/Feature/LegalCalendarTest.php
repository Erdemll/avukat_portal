<?php

use App\Models\Deadline;
use App\Models\Hearing;
use App\Models\LegalTask;

it('combines only visible hearings deadlines and tasks in the legal calendar', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $visibleCase = legalCaseFile($manager, [$lawyer]);
    $hiddenCase = legalCaseFile($manager, [$otherLawyer]);
    Hearing::factory()->create(['case_file_id' => $visibleCase, 'lawyer_id' => $lawyer, 'created_by' => $lawyer, 'title' => 'Görünen duruşma', 'hearing_at' => now()->addDay()]);
    Deadline::factory()->create(['case_file_id' => $visibleCase, 'assigned_lawyer_id' => $lawyer, 'created_by' => $lawyer, 'title' => 'Görünen süre', 'due_at' => now()->addDays(2)]);
    LegalTask::factory()->create(['case_file_id' => $visibleCase, 'assigned_to' => $lawyer, 'created_by' => $lawyer, 'title' => 'Görünen görev', 'due_at' => now()->addDays(3)]);
    Hearing::factory()->create(['case_file_id' => $hiddenCase, 'lawyer_id' => $otherLawyer, 'created_by' => $otherLawyer, 'title' => 'Gizli duruşma', 'hearing_at' => now()->addDay()]);

    $this->actingAs($lawyer)->get(route('legal-calendar.index'))
        ->assertOk()
        ->assertSee('Görünen duruşma')
        ->assertSee('Görünen süre')
        ->assertSee('Görünen görev')
        ->assertDontSee('Gizli duruşma');
});

it('rejects calendar ranges longer than one year', function () {
    $this->actingAs(userWithRole('lawyer'))->get(route('legal-calendar.index', [
        'from' => '2026-01-01',
        'to' => '2027-02-01',
    ]))->assertUnprocessable();
});
