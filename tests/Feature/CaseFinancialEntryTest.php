<?php

use App\FinancialEntryType;
use App\Models\CaseFinancialEntry;
use App\Notifications\FinancialEntryCreatedNotification;
use Illuminate\Support\Facades\Notification;

it('lets managers create immutable financial entries visible to case lawyers', function () {
    Notification::fake();
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($manager)->post(route('financial-entries.store'), [
        'case_file_id' => $caseFile->id,
        'type' => 'receivable',
        'amount' => '500000.00',
        'currency' => 'TRY',
        'description' => 'Dava konusu alacak',
        'transaction_date' => '2026-09-19',
    ])->assertRedirect();

    $entry = CaseFinancialEntry::query()->firstOrFail();
    expect($entry->type)->toBe(FinancialEntryType::Receivable);
    expect(fn () => $entry->update(['description' => 'değiştirildi']))->toThrow(LogicException::class);
    $this->actingAs($lawyer)->get(route('financial-entries.index', ['case_file' => $caseFile->id]))->assertOk()->assertSee('500.000,00');
    Notification::assertSentTo($lawyer, FinancialEntryCreatedNotification::class);
});

it('restricts financial writes to managers and validates positive supported amounts', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $payload = ['case_file_id' => $caseFile->id, 'type' => 'payment', 'amount' => '-10', 'currency' => 'XYZ', 'description' => 'Geçersiz', 'transaction_date' => '2026-09-19'];

    $this->actingAs($lawyer)->post(route('financial-entries.store'), $payload)->assertForbidden();
    $this->actingAs($manager)->post(route('financial-entries.store'), $payload)->assertSessionHasErrors(['amount', 'currency']);
});

it('reverses each financial entry at most once without changing the original', function () {
    $manager = userWithRole('manager');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $entry = CaseFinancialEntry::factory()->create(['case_file_id' => $caseFile, 'created_by' => $manager, 'amount' => '150000.00']);

    $this->actingAs($manager)->post(route('financial-entries.reverse', $entry), [
        'reason' => 'Mükerrer kayıt',
        'transaction_date' => '2026-09-19',
    ])->assertRedirect();

    $reversal = CaseFinancialEntry::query()->where('reversal_of_id', $entry->id)->firstOrFail();
    expect($reversal->type)->toBe(FinancialEntryType::Reversal);
    expect($reversal->amount)->toBe($entry->amount);
    $this->actingAs($manager)->post(route('financial-entries.reverse', $entry), ['reason' => 'Tekrar', 'transaction_date' => '2026-09-19'])->assertForbidden();
    expect(CaseFinancialEntry::query()->count())->toBe(2);
});
