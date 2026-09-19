<?php

namespace App\Services;

use App\AuditAction;
use App\DeadlineStatus;
use App\Models\CaseFile;
use App\Models\Deadline;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeadlineService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Deadline
    {
        return DB::transaction(function () use ($data, $actor): Deadline {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            if ($caseFile->status->value === 'closed') {
                throw ValidationException::withMessages(['case_file_id' => 'Kapalı dosyaya yeni süre eklenemez.']);
            }
            $this->validateLawyer($caseFile, $data['assigned_lawyer_id'] ?? null);
            $deadline = new Deadline(Arr::except($data, ['case_file_id', 'assigned_lawyer_id']));
            $deadline->case_file_id = $caseFile->id;
            $deadline->assigned_lawyer_id = $data['assigned_lawyer_id'] ?? null;
            $deadline->created_by = $actor->id;
            $deadline->completed_at = ($data['status'] ?? null) === DeadlineStatus::Completed->value ? now() : null;
            $deadline->save();
            $this->audit->log(AuditAction::DeadlineCreated, $actor, auditable: $deadline, description: 'Hukuki süre oluşturuldu.', newValues: $deadline->only(['case_file_id', 'title', 'description', 'starts_at', 'due_at', 'assigned_lawyer_id', 'status', 'completed_at']), caseFile: $caseFile);

            return $deadline;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Deadline $deadline, array $data, User $actor): Deadline
    {
        return DB::transaction(function () use ($deadline, $data, $actor): Deadline {
            $deadline = Deadline::query()->lockForUpdate()->findOrFail($deadline->id);
            $caseFiles = CaseFile::query()
                ->whereIn('id', collect([$deadline->case_file_id, $data['case_file_id']])->unique()->sort()->values())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sourceCaseFile = $caseFiles->get($deadline->case_file_id);
            $caseFile = $caseFiles->get((int) $data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $sourceCaseFile);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            if ($caseFile->status->value === 'closed' && $caseFile->id !== $sourceCaseFile->id) {
                throw ValidationException::withMessages(['case_file_id' => 'Süre kapalı bir dosyaya taşınamaz.']);
            }
            if ($deadline->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Süre başka bir kullanıcı tarafından güncellendi.']);
            }
            $oldValues = $deadline->only(['case_file_id', 'title', 'description', 'starts_at', 'due_at', 'assigned_lawyer_id', 'status', 'completed_at']);
            $this->validateLawyer($caseFile, $data['assigned_lawyer_id'] ?? null);
            $deadline->fill(Arr::except($data, ['case_file_id', 'assigned_lawyer_id', 'lock_version']));
            $deadline->case_file_id = $data['case_file_id'];
            $deadline->assigned_lawyer_id = $data['assigned_lawyer_id'] ?? null;
            $deadline->completed_at = $data['status'] === DeadlineStatus::Completed->value ? ($deadline->completed_at ?? now()) : null;
            if ($deadline->isDirty(['case_file_id', 'assigned_lawyer_id', 'due_at', 'status'])) {
                $deadline->reminder_sent_at = null;
            }
            $deadline->lock_version++;
            $deadline->save();
            $this->audit->log(AuditAction::DeadlineUpdated, $actor, auditable: $deadline, description: 'Hukuki süre güncellendi.', oldValues: $oldValues, newValues: $deadline->only(['case_file_id', 'title', 'description', 'starts_at', 'due_at', 'assigned_lawyer_id', 'status', 'completed_at']), caseFile: $caseFile);

            return $deadline;
        });
    }

    private function validateLawyer(CaseFile $caseFile, mixed $lawyerId): void
    {
        if ($lawyerId === null) {
            return;
        }

        $lawyer = User::query()->lockForUpdate()->findOrFail((int) $lawyerId);
        if (! $lawyer->is_active || ! $lawyer->isLawyer() || ! $caseFile->assignments()->where('lawyer_id', $lawyer->id)->whereNull('ended_at')->exists()) {
            throw ValidationException::withMessages(['assigned_lawyer_id' => 'Süre sorumlusu dosyanın aktif avukatlarından biri olmalıdır.']);
        }
    }
}
