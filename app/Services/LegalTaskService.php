<?php

namespace App\Services;

use App\AuditAction;
use App\LegalTaskStatus;
use App\Models\CaseFile;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LegalTaskService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): LegalTask
    {
        return DB::transaction(function () use ($data, $actor): LegalTask {
            $caseFile = ! empty($data['case_file_id']) ? CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']) : null;
            if ($caseFile !== null) {
                Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
                if ($caseFile->status->value === 'closed') {
                    throw ValidationException::withMessages(['case_file_id' => 'Kapalı dosyaya yeni görev eklenemez.']);
                }
            }
            $this->validateAssignee($caseFile, (int) $data['assigned_to'], $actor);
            $task = new LegalTask(Arr::except($data, ['case_file_id', 'assigned_to']));
            $task->case_file_id = $caseFile?->id;
            $task->assigned_to = $data['assigned_to'];
            $task->created_by = $actor->id;
            $task->completed_at = ($data['status'] ?? null) === LegalTaskStatus::Completed->value ? now() : null;
            $task->save();
            $this->audit->log(AuditAction::LegalTaskCreated, $actor, auditable: $task, description: 'Hukuki görev oluşturuldu.', newValues: $task->only(['case_file_id', 'assigned_to', 'title', 'description', 'priority', 'due_at', 'status', 'completed_at']), caseFile: $caseFile);

            return $task;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(LegalTask $task, array $data, User $actor): LegalTask
    {
        return DB::transaction(function () use ($task, $data, $actor): LegalTask {
            $task = LegalTask::query()->lockForUpdate()->findOrFail($task->id);
            $targetCaseFileId = ! empty($data['case_file_id']) ? (int) $data['case_file_id'] : null;
            $caseFiles = CaseFile::query()
                ->whereIn('id', collect([$task->case_file_id, $targetCaseFileId])->filter()->unique()->sort()->values())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sourceCaseFile = $task->case_file_id !== null ? $caseFiles->get($task->case_file_id) : null;
            $caseFile = $targetCaseFileId !== null ? $caseFiles->get($targetCaseFileId) : null;
            if ($sourceCaseFile !== null) {
                Gate::forUser($actor)->authorize('manageLegalOperations', $sourceCaseFile);
            } else {
                Gate::forUser($actor)->authorize('update', $task);
            }
            if ($task->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Görev başka bir kullanıcı tarafından güncellendi.']);
            }
            if ($task->case_file_id !== null && $caseFile?->id !== $task->case_file_id && ! $actor->isManager()) {
                throw ValidationException::withMessages(['case_file_id' => 'Dosyaya bağlı görev başka dosyaya taşınamaz veya kişisel göreve dönüştürülemez.']);
            }
            if ($caseFile !== null) {
                Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
                if ($caseFile->status->value === 'closed' && $caseFile->id !== $sourceCaseFile?->id) {
                    throw ValidationException::withMessages(['case_file_id' => 'Görev kapalı bir dosyaya taşınamaz.']);
                }
            }
            $this->validateAssignee($caseFile, (int) $data['assigned_to'], $actor);
            $oldValues = $task->only(['case_file_id', 'assigned_to', 'title', 'description', 'priority', 'due_at', 'status', 'completed_at']);
            $task->fill(Arr::except($data, ['case_file_id', 'assigned_to', 'lock_version']));
            $task->case_file_id = $caseFile?->id;
            $task->assigned_to = $data['assigned_to'];
            $task->completed_at = $data['status'] === LegalTaskStatus::Completed->value ? ($task->completed_at ?? now()) : null;
            $task->lock_version++;
            $task->save();
            $this->audit->log(AuditAction::LegalTaskUpdated, $actor, auditable: $task, description: 'Hukuki görev güncellendi.', oldValues: $oldValues, newValues: $task->only(['case_file_id', 'assigned_to', 'title', 'description', 'priority', 'due_at', 'status', 'completed_at']), caseFile: $caseFile);

            return $task;
        });
    }

    private function validateAssignee(?CaseFile $caseFile, int $assigneeId, User $actor): void
    {
        $assignee = User::query()->lockForUpdate()->findOrFail($assigneeId);
        if (! $assignee->is_active || (! $assignee->isLawyer() && ! $assignee->isManager())) {
            throw ValidationException::withMessages(['assigned_to' => 'Görev yalnız aktif hukuk kullanıcılarına atanabilir.']);
        }
        if (! $actor->isManager() && $caseFile === null && $assignee->id !== $actor->id) {
            throw ValidationException::withMessages(['assigned_to' => 'Dosyasız kişisel görev yalnız kendinize atanabilir.']);
        }
        if (! $actor->isManager() && $caseFile !== null && $assignee->id !== $actor->id && ! $caseFile->assignments()->where('lawyer_id', $assignee->id)->whereNull('ended_at')->exists()) {
            throw ValidationException::withMessages(['assigned_to' => 'Görev yalnız size veya dosyanın aktif avukatlarından birine atanabilir.']);
        }
    }
}
