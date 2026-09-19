<?php

namespace App\Services;

use App\AuditAction;
use App\CaseEventRelationType;
use App\CaseFilePartyRole;
use App\CaseFileStatus;
use App\CasePartySide;
use App\Models\CaseFile;
use App\Models\CaseFileEvent;
use App\Models\CaseFileParty;
use App\Models\CaseFileStatusHistory;
use App\Models\CaseProceeding;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaseFileManagementService
{
    public function __construct(
        private CaseFileNumberService $numbers,
        private CaseFileAssignmentService $assignments,
        private DocumentFolderService $documentFolders,
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor, ?Event $sourceEvent = null): CaseFile
    {
        return DB::transaction(function () use ($data, $actor, $sourceEvent): CaseFile {
            Gate::forUser($actor)->authorize('create', CaseFile::class);
            if ($sourceEvent !== null) {
                $sourceEvent = Event::query()->whereKey($sourceEvent)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('view', $sourceEvent);
                Gate::forUser($actor)->authorize('create', CaseFile::class);
                if (CaseFileEvent::query()->where('event_id', $sourceEvent->id)->where('relation_type', CaseEventRelationType::Origin)->exists()) {
                    throw ValidationException::withMessages(['event' => 'Bu hukuki talep daha önce bir dosyaya dönüştürülmüş.']);
                }
            }

            $caseFile = new CaseFile(Arr::only($data, ['case_type_id', 'title', 'priority', 'description', 'opened_at']));
            $caseFile->forceFill([
                'case_no' => $this->numbers->next(),
                'created_by' => $actor->id,
                'status' => CaseFileStatus::Active,
            ])->save();

            $statusHistory = new CaseFileStatusHistory([
                'from_status' => null,
                'to_status' => CaseFileStatus::Active,
                'changed_at' => now(),
                'reason' => 'Dosya oluşturuldu.',
            ]);
            $statusHistory->case_file_id = $caseFile->id;
            $statusHistory->changed_by = $actor->id;
            $statusHistory->save();

            $this->documentFolders->ensureDefaults($caseFile, $actor);

            $this->storeProceeding($caseFile, $data);
            $this->attachClients($caseFile, $data['client_party_ids'] ?? [], $actor);

            $lawyerIds = $actor->isManager() ? $data['lawyer_ids'] : [$actor->id];
            $leadLawyerId = $actor->isManager() ? (int) $data['lead_lawyer_id'] : $actor->id;
            $caseFile = $this->assignments->sync($caseFile, $actor, $lawyerIds, $leadLawyerId);

            if ($sourceEvent !== null) {
                $link = new CaseFileEvent(['relation_type' => CaseEventRelationType::Origin, 'linked_at' => now()]);
                $link->case_file_id = $caseFile->id;
                $link->event_id = $sourceEvent->id;
                $link->linked_by = $actor->id;
                $link->save();
                $this->audit->log(AuditAction::CaseFileEventLinked, $actor, $sourceEvent, $link, 'Hukuki talep dosyaya dönüştürüldü.', newValues: ['case_file_id' => $caseFile->id], caseFile: $caseFile);
            }

            $this->audit->log(
                AuditAction::CaseFileCreated,
                $actor,
                auditable: $caseFile,
                description: 'Hukuki dosya oluşturuldu.',
                newValues: [
                    ...$caseFile->only(['case_no', 'case_type_id', 'title', 'status', 'priority', 'opened_at']),
                    'client_party_ids' => collect($data['client_party_ids'] ?? [])->map(fn ($id): int => (int) $id)->all(),
                    'proceeding' => Arr::only($data, ['proceeding_type', 'courthouse', 'authority_name', 'principal_year', 'principal_number', 'decision_year', 'decision_number', 'external_file_number']),
                ],
                caseFile: $caseFile,
            );

            return $caseFile->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function update(CaseFile $caseFile, array $data, User $actor): CaseFile
    {
        return DB::transaction(function () use ($caseFile, $data, $actor): CaseFile {
            $lockedCaseFile = $this->lockedCaseFile($caseFile, (int) $data['lock_version']);
            Gate::forUser($actor)->authorize('update', $lockedCaseFile);
            $oldValues = $lockedCaseFile->only(['case_type_id', 'title', 'priority', 'description', 'opened_at']);
            $oldProceeding = $lockedCaseFile->proceedings()->oldest('id')->first()?->only(['type', 'courthouse', 'authority_name', 'court_type', 'principal_year', 'principal_number', 'decision_year', 'decision_number', 'external_file_number']);
            $lockedCaseFile->fill(Arr::only($data, ['case_type_id', 'title', 'priority', 'description', 'opened_at']));
            $lockedCaseFile->lock_version++;
            $lockedCaseFile->save();
            $this->storeProceeding($lockedCaseFile, $data);

            $this->audit->log(
                AuditAction::CaseFileUpdated,
                $actor,
                auditable: $lockedCaseFile,
                description: 'Hukuki dosya bilgileri güncellendi.',
                oldValues: [...$oldValues, 'proceeding' => $oldProceeding],
                newValues: [
                    ...$lockedCaseFile->only(['case_type_id', 'title', 'priority', 'description', 'opened_at']),
                    'proceeding' => $lockedCaseFile->proceedings()->oldest('id')->first()?->only(['type', 'courthouse', 'authority_name', 'court_type', 'principal_year', 'principal_number', 'decision_year', 'decision_number', 'external_file_number']),
                ],
                caseFile: $lockedCaseFile,
            );

            return $lockedCaseFile->refresh();
        });
    }

    public function changeStatus(CaseFile $caseFile, CaseFileStatus $status, User $actor, ?string $reason, int $expectedLockVersion): CaseFile
    {
        return DB::transaction(function () use ($caseFile, $status, $actor, $reason, $expectedLockVersion): CaseFile {
            $lockedCaseFile = $this->lockedCaseFile($caseFile, $expectedLockVersion);
            Gate::forUser($actor)->authorize('update', $lockedCaseFile);
            $oldStatus = $lockedCaseFile->status;
            if ($oldStatus === $status) {
                return $lockedCaseFile;
            }

            if (! in_array($status, $oldStatus->transitions(), true)) {
                throw ValidationException::withMessages(['status' => 'Bu dosya durumu geçişine izin verilmiyor.']);
            }
            if ($oldStatus === CaseFileStatus::Closed && ! $actor->isManager()) {
                throw ValidationException::withMessages(['status' => 'Kapalı dosyayı yalnız yönetici yeniden açabilir.']);
            }

            $lockedCaseFile->forceFill([
                'status' => $status,
                'closed_at' => $status === CaseFileStatus::Closed ? today() : null,
                'lock_version' => $lockedCaseFile->lock_version + 1,
            ])->save();

            $history = new CaseFileStatusHistory([
                'from_status' => $oldStatus,
                'to_status' => $status,
                'reason' => $reason,
                'changed_at' => now(),
            ]);
            $history->case_file_id = $lockedCaseFile->id;
            $history->changed_by = $actor->id;
            $history->save();

            $this->audit->log(
                AuditAction::CaseFileStatusChanged,
                $actor,
                auditable: $lockedCaseFile,
                description: 'Hukuki dosya durumu değiştirildi.',
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => $status->value, 'reason' => $reason],
                caseFile: $lockedCaseFile,
            );

            return $lockedCaseFile->refresh();
        });
    }

    private function lockedCaseFile(CaseFile $caseFile, int $expectedLockVersion): CaseFile
    {
        $lockedCaseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
        if ($lockedCaseFile->lock_version !== $expectedLockVersion) {
            throw ValidationException::withMessages(['lock_version' => 'Dosya başka bir kullanıcı tarafından güncellendi. Sayfayı yenileyip tekrar deneyin.']);
        }

        return $lockedCaseFile;
    }

    /** @param array<string, mixed> $data */
    private function storeProceeding(CaseFile $caseFile, array $data): void
    {
        if (empty($data['proceeding_type'])) {
            return;
        }

        $proceeding = $caseFile->proceedings()->oldest('id')->first() ?? new CaseProceeding;
        $proceeding->fill([
            'type' => $data['proceeding_type'],
            ...Arr::only($data, ['courthouse', 'authority_name', 'court_type', 'principal_year', 'principal_number', 'decision_year', 'decision_number', 'external_file_number']),
        ]);
        $proceeding->case_file_id = $caseFile->id;
        $proceeding->opened_at ??= $caseFile->opened_at;
        $proceeding->save();
    }

    /** @param array<int, int|string> $partyIds */
    private function attachClients(CaseFile $caseFile, array $partyIds, User $actor): void
    {
        foreach (collect($partyIds)->map(fn ($id): int => (int) $id)->unique()->values() as $index => $partyId) {
            $caseParty = new CaseFileParty([
                'role' => CaseFilePartyRole::Client,
                'side' => CasePartySide::Own,
                'is_primary' => $index === 0,
                'joined_at' => now(),
            ]);
            $caseParty->case_file_id = $caseFile->id;
            $caseParty->party_id = $partyId;
            $caseParty->added_by = $actor->id;
            $caseParty->save();
            $this->audit->log(AuditAction::CaseFilePartyAdded, $actor, auditable: $caseParty, description: 'Hukuki dosyaya müvekkil eklendi.', newValues: ['party_id' => $partyId, 'role' => CaseFilePartyRole::Client->value, 'side' => CasePartySide::Own->value], caseFile: $caseFile);
        }
    }
}
