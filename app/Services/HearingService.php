<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HearingService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Hearing
    {
        return DB::transaction(function () use ($data, $actor): Hearing {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            if ($caseFile->status->value === 'closed') {
                throw ValidationException::withMessages(['case_file_id' => 'Kapalı dosyaya yeni duruşma eklenemez.']);
            }
            $this->validateLawyer($caseFile, $data['lawyer_id'] ?? null);
            $hearing = new Hearing(Arr::except($data, ['case_file_id', 'lawyer_id']));
            $hearing->case_file_id = $caseFile->id;
            $hearing->lawyer_id = $data['lawyer_id'] ?? null;
            $hearing->created_by = $actor->id;
            $hearing->save();
            $this->audit->log(AuditAction::HearingCreated, $actor, auditable: $hearing, description: 'Duruşma oluşturuldu.', newValues: $hearing->only(['case_file_id', 'title', 'court', 'hearing_at', 'hearing_type', 'description', 'lawyer_id', 'status', 'result', 'next_hearing_at']), caseFile: $caseFile);

            return $hearing;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Hearing $hearing, array $data, User $actor): Hearing
    {
        return DB::transaction(function () use ($hearing, $data, $actor): Hearing {
            $hearing = Hearing::query()->lockForUpdate()->findOrFail($hearing->id);
            $caseFiles = CaseFile::query()
                ->whereIn('id', collect([$hearing->case_file_id, $data['case_file_id']])->unique()->sort()->values())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sourceCaseFile = $caseFiles->get($hearing->case_file_id);
            $caseFile = $caseFiles->get((int) $data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $sourceCaseFile);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            if ($caseFile->status->value === 'closed' && $caseFile->id !== $sourceCaseFile->id) {
                throw ValidationException::withMessages(['case_file_id' => 'Duruşma kapalı bir dosyaya taşınamaz.']);
            }
            if ($hearing->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Duruşma başka bir kullanıcı tarafından güncellendi.']);
            }
            $oldValues = $hearing->only(['case_file_id', 'title', 'court', 'hearing_at', 'hearing_type', 'description', 'lawyer_id', 'status', 'result', 'next_hearing_at']);
            $this->validateLawyer($caseFile, $data['lawyer_id'] ?? null);
            $hearing->fill(Arr::except($data, ['case_file_id', 'lawyer_id', 'lock_version']));
            $hearing->case_file_id = $data['case_file_id'];
            $hearing->lawyer_id = $data['lawyer_id'] ?? null;
            if ($hearing->isDirty(['case_file_id', 'lawyer_id', 'hearing_at', 'status'])) {
                $hearing->reminder_sent_at = null;
            }
            $hearing->lock_version++;
            $hearing->save();
            $this->audit->log(AuditAction::HearingUpdated, $actor, auditable: $hearing, description: 'Duruşma güncellendi.', oldValues: $oldValues, newValues: $hearing->only(['case_file_id', 'title', 'court', 'hearing_at', 'hearing_type', 'description', 'lawyer_id', 'status', 'result', 'next_hearing_at']), caseFile: $caseFile);

            return $hearing;
        });
    }

    private function validateLawyer(CaseFile $caseFile, mixed $lawyerId): void
    {
        if ($lawyerId === null) {
            return;
        }

        $lawyer = User::query()->lockForUpdate()->findOrFail((int) $lawyerId);
        if (! $lawyer->is_active || ! $lawyer->isLawyer() || ! $caseFile->assignments()->where('lawyer_id', $lawyer->id)->whereNull('ended_at')->exists()) {
            throw ValidationException::withMessages(['lawyer_id' => 'Duruşma avukatı dosyanın aktif avukatlarından biri olmalıdır.']);
        }
    }
}
