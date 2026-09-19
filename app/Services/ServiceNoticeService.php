<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\ServiceNotice;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ServiceNoticeService
{
    public function __construct(private AuditService $audit, private CaseWorkflowNotificationService $notifications) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): ServiceNotice
    {
        return DB::transaction(function () use ($data, $actor): ServiceNotice {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            $notice = new ServiceNotice(Arr::except($data, ['case_file_id', 'document_id']));
            $notice->case_file_id = $caseFile->id;
            $notice->document_id = $data['document_id'] ?? null;
            $notice->created_by = $actor->id;
            $notice->save();
            $this->audit->log(AuditAction::ServiceNoticeCreated, $actor, auditable: $notice, description: 'Tebligat kaydı oluşturuldu.', newValues: $notice->only(['type', 'sender', 'recipient', 'notification_date', 'service_date', 'document_id']), caseFile: $caseFile);
            DB::afterCommit(fn () => $this->notifications->serviceNoticeCreated($notice, $actor));

            return $notice;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(ServiceNotice $notice, array $data, User $actor): ServiceNotice
    {
        return DB::transaction(function () use ($notice, $data, $actor): ServiceNotice {
            $notice = ServiceNotice::query()->lockForUpdate()->findOrFail($notice->id);
            $caseFiles = CaseFile::query()->whereIn('id', collect([$notice->case_file_id, $data['case_file_id']])->unique()->sort()->values())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sourceCaseFile = $caseFiles->get($notice->case_file_id);
            $caseFile = $caseFiles->get((int) $data['case_file_id']);
            Gate::forUser($actor)->authorize('manageLegalOperations', $sourceCaseFile);
            Gate::forUser($actor)->authorize('manageLegalOperations', $caseFile);
            if ($notice->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Tebligat başka bir kullanıcı tarafından güncellendi.']);
            }
            $oldValues = $notice->only(['case_file_id', 'type', 'sender', 'recipient', 'notification_date', 'service_date', 'description', 'document_id']);
            $notice->fill(Arr::except($data, ['case_file_id', 'document_id', 'lock_version']));
            $notice->case_file_id = $caseFile->id;
            $notice->document_id = $data['document_id'] ?? null;
            $notice->lock_version++;
            $notice->save();
            $this->audit->log(AuditAction::ServiceNoticeUpdated, $actor, auditable: $notice, description: 'Tebligat kaydı güncellendi.', oldValues: $oldValues, newValues: $notice->only(['case_file_id', 'type', 'sender', 'recipient', 'notification_date', 'service_date', 'description', 'document_id']), caseFile: $caseFile);

            return $notice;
        });
    }
}
