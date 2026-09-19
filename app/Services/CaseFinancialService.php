<?php

namespace App\Services;

use App\AuditAction;
use App\FinancialEntryType;
use App\Models\CaseFile;
use App\Models\CaseFinancialEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CaseFinancialService
{
    public function __construct(private AuditService $audit, private CaseWorkflowNotificationService $notifications) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): CaseFinancialEntry
    {
        return DB::transaction(function () use ($data, $actor): CaseFinancialEntry {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']);
            Gate::forUser($actor)->authorize('create', CaseFinancialEntry::class);
            Gate::forUser($actor)->authorize('view', $caseFile);
            $entry = new CaseFinancialEntry([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'currency' => Str::upper($data['currency']),
                'description' => $data['description'],
                'transaction_date' => $data['transaction_date'],
            ]);
            $entry->case_file_id = $caseFile->id;
            $entry->created_by = $actor->id;
            $entry->save();
            $this->audit->log(AuditAction::FinancialEntryCreated, $actor, auditable: $entry, description: 'Dosya finans hareketi oluşturuldu.', newValues: $entry->only(['type', 'amount', 'currency', 'description', 'transaction_date']), caseFile: $caseFile);
            DB::afterCommit(fn () => $this->notifications->financialEntryCreated($entry, $actor));

            return $entry;
        });
    }

    /** @param array{reason: string, transaction_date: string} $data */
    public function reverse(CaseFinancialEntry $entry, array $data, User $actor): CaseFinancialEntry
    {
        return DB::transaction(function () use ($entry, $data, $actor): CaseFinancialEntry {
            $entry = CaseFinancialEntry::query()->with('reversal')->lockForUpdate()->findOrFail($entry->id);
            Gate::forUser($actor)->authorize('reverse', $entry);
            if ($entry->type === FinancialEntryType::Reversal || $entry->reversal !== null) {
                throw ValidationException::withMessages(['entry' => 'Bu finans hareketi daha önce ters kaydedilmiş veya ters kayıttır.']);
            }

            $reversal = new CaseFinancialEntry([
                'type' => FinancialEntryType::Reversal,
                'amount' => $entry->amount,
                'currency' => $entry->currency,
                'description' => 'Ters kayıt: '.$data['reason'],
                'transaction_date' => $data['transaction_date'],
            ]);
            $reversal->case_file_id = $entry->case_file_id;
            $reversal->reversal_of_id = $entry->id;
            $reversal->created_by = $actor->id;
            $reversal->save();
            $this->audit->log(AuditAction::FinancialEntryReversed, $actor, auditable: $reversal, description: 'Dosya finans hareketi ters kaydedildi.', oldValues: ['entry_id' => $entry->id, 'type' => $entry->type->value, 'amount' => $entry->amount, 'currency' => $entry->currency], newValues: ['reversal_id' => $reversal->id, 'reason' => $data['reason']], caseFile: $entry->caseFile);

            return $reversal;
        });
    }
}
