<?php

use App\Models\CaseFinancialEntry;

it('shows managers operational aggregates and exports them as csv', function () {
    $manager = userWithRole('manager');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $caseFile->forceFill(['opened_at' => '2026-09-01'])->save();
    CaseFinancialEntry::factory()->create(['case_file_id' => $caseFile, 'created_by' => $manager, 'amount' => 1250, 'currency' => 'TRY', 'transaction_date' => '2026-09-01']);

    $this->actingAs($manager)->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Hukuk operasyon raporu')
        ->assertSee('1.250,00 TRY');

    $this->actingAs($manager)->get(route('reports.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();
});

it('restricts management reports to managers', function () {
    $this->actingAs(userWithRole('lawyer'))->get(route('reports.index'))->assertForbidden();
    $this->actingAs(userWithRole('employee'))->get(route('reports.export'))->assertForbidden();
});
