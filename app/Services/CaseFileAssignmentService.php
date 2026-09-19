<?php

namespace App\Services;

use App\AuditAction;
use App\CaseAssignmentRole;
use App\Models\CaseFile;
use App\Models\CaseFileAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaseFileAssignmentService
{
    public function __construct(private AuditService $audit, private CaseFileNotificationService $notifications) {}

    /** @param array<int, int|string> $lawyerIds */
    public function sync(CaseFile $caseFile, User $actor, array $lawyerIds, int $leadLawyerId, ?string $reason = null, ?int $expectedLockVersion = null): CaseFile
    {
        $lawyerIds = collect($lawyerIds)->map(fn ($id): int => (int) $id)->unique()->values();

        return DB::transaction(function () use ($caseFile, $actor, $lawyerIds, $leadLawyerId, $reason, $expectedLockVersion): CaseFile {
            $lockedCaseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
            if ($expectedLockVersion !== null) {
                Gate::forUser($actor)->authorize('assign', $lockedCaseFile);
            } else {
                Gate::forUser($actor)->authorize('create', CaseFile::class);
            }
            if ($expectedLockVersion !== null && $lockedCaseFile->lock_version !== $expectedLockVersion) {
                throw ValidationException::withMessages(['lock_version' => 'Dosya başka bir kullanıcı tarafından güncellendi. Sayfayı yenileyip tekrar deneyin.']);
            }

            $lawyers = User::query()
                ->whereIn('id', $lawyerIds)
                ->lockForUpdate()
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))
                ->get()
                ->keyBy('id');
            if ($lawyers->count() !== $lawyerIds->count() || ! $lawyers->has($leadLawyerId)) {
                throw ValidationException::withMessages(['lawyer_ids' => 'Atamalar yalnız aktif avukatlara yapılabilir.']);
            }

            $activeAssignments = $lockedCaseFile->assignments()->whereNull('ended_at')->lockForUpdate()->get()->keyBy('lawyer_id');
            $oldLawyerIds = $activeAssignments->keys()->map(fn ($id): int => (int) $id)->values()->all();
            $oldLeadLawyerId = $activeAssignments->first(fn (CaseFileAssignment $assignment): bool => $assignment->role === CaseAssignmentRole::Lead)?->lawyer_id;

            foreach ($activeAssignments as $assignment) {
                if ($assignment->role === CaseAssignmentRole::Lead) {
                    $assignment->forceFill(['role' => CaseAssignmentRole::Lawyer])->save();
                }
            }

            foreach ($activeAssignments as $lawyerId => $assignment) {
                if (! $lawyerIds->contains((int) $lawyerId)) {
                    $assignment->forceFill([
                        'ended_at' => now(),
                        'ended_by' => $actor->id,
                        'reason' => $reason,
                    ])->save();
                }
            }

            $newLawyerIds = [];
            foreach ($lawyerIds as $lawyerId) {
                $assignment = $activeAssignments->get($lawyerId);
                if ($assignment === null) {
                    $assignment = new CaseFileAssignment;
                    $assignment->case_file_id = $lockedCaseFile->id;
                    $assignment->lawyer_id = $lawyerId;
                    $assignment->assigned_by = $actor->id;
                    $assignment->started_at = now();
                    $newLawyerIds[] = $lawyerId;
                }

                $assignment->forceFill([
                    'role' => $lawyerId === $leadLawyerId ? CaseAssignmentRole::Lead : CaseAssignmentRole::Lawyer,
                    'reason' => $reason,
                ])->save();
            }

            $lockedCaseFile->forceFill(['lock_version' => $lockedCaseFile->lock_version + 1])->save();
            $this->audit->log(
                AuditAction::CaseFileAssignmentsChanged,
                $actor,
                auditable: $lockedCaseFile,
                description: 'Hukuki dosya avukat atamaları güncellendi.',
                oldValues: ['lawyer_ids' => $oldLawyerIds, 'lead_lawyer_id' => $oldLeadLawyerId],
                newValues: ['lawyer_ids' => $lawyerIds->all(), 'lead_lawyer_id' => $leadLawyerId],
                caseFile: $lockedCaseFile,
            );

            $newLawyers = $lawyers->only($newLawyerIds)->values();
            DB::afterCommit(fn () => $this->notifications->assigned($lockedCaseFile, $newLawyers, $actor));

            return $lockedCaseFile->refresh();
        });
    }
}
