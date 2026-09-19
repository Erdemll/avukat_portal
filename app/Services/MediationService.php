<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\Mediation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MediationService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Mediation
    {
        return DB::transaction(function () use ($data, $actor): Mediation {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            $mediation = new Mediation(Arr::except($data, ['case_file_id']));
            $mediation->case_file_id = $caseFile->id;
            $mediation->created_by = $actor->id;
            $mediation->save();
            $this->audit->log(AuditAction::MediationCreated, $actor, auditable: $mediation, description: 'Arabuluculuk kaydı oluşturuldu.', newValues: $mediation->only(['mediation_file_no', 'mediator_name', 'application_date', 'meeting_date', 'completion_date', 'status', 'result']), caseFile: $caseFile);

            return $mediation;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Mediation $mediation, array $data, User $actor): Mediation
    {
        return DB::transaction(function () use ($mediation, $data, $actor): Mediation {
            $mediation = Mediation::query()->lockForUpdate()->findOrFail($mediation->id);
            $caseFiles = CaseFile::query()->whereIn('id', collect([$mediation->case_file_id, $data['case_file_id']])->unique()->sort()->values())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sourceCaseFile = $caseFiles->get($mediation->case_file_id);
            $caseFile = $caseFiles->get((int) $data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $sourceCaseFile);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            if ($mediation->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Arabuluculuk kaydı başka bir kullanıcı tarafından güncellendi.']);
            }
            $oldValues = $mediation->only(['case_file_id', 'mediation_file_no', 'mediator_name', 'application_date', 'meeting_date', 'completion_date', 'status', 'result']);
            $mediation->fill(Arr::except($data, ['case_file_id', 'lock_version']));
            $mediation->case_file_id = $caseFile->id;
            $mediation->lock_version++;
            $mediation->save();
            $this->audit->log(AuditAction::MediationUpdated, $actor, auditable: $mediation, description: 'Arabuluculuk kaydı güncellendi.', oldValues: $oldValues, newValues: $mediation->only(['case_file_id', 'mediation_file_no', 'mediator_name', 'application_date', 'meeting_date', 'completion_date', 'status', 'result']), caseFile: $caseFile);

            return $mediation;
        });
    }
}
