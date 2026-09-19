<?php

namespace App\Services;

use App\AuditAction;
use App\CaseAssignmentRequestStatus;
use App\CaseAssignmentRequestType;
use App\CaseAssignmentRole;
use App\Models\CaseAssignmentRequest;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaseAssignmentRequestService
{
    public function __construct(
        private AuditService $audit,
        private CaseFileAssignmentService $assignments,
        private CaseWorkflowNotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): CaseAssignmentRequest
    {
        try {
            return DB::transaction(function () use ($data, $actor): CaseAssignmentRequest {
                $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']);
                Gate::forUser($actor)->authorize('create', CaseAssignmentRequest::class);
                if ($caseFile->status->value === 'closed') {
                    throw ValidationException::withMessages(['case_file_id' => 'Kapalı dosya için talep oluşturulamaz.']);
                }
                $isAssigned = $caseFile->assignments()->where('lawyer_id', $actor->id)->whereNull('ended_at')->exists();
                $type = CaseAssignmentRequestType::from($data['type']);
                if ($type === CaseAssignmentRequestType::Transfer && ! $isAssigned) {
                    throw ValidationException::withMessages(['case_file_id' => 'Yalnız atandığınız dosya için devir talebi oluşturabilirsiniz.']);
                }
                if ($type === CaseAssignmentRequestType::Claim && $isAssigned) {
                    throw ValidationException::withMessages(['case_file_id' => 'Zaten atandığınız dosya için atama talebi oluşturamazsınız.']);
                }
                $target = ! empty($data['requested_to']) ? User::query()->lockForUpdate()->findOrFail($data['requested_to']) : null;
                if ($target !== null && (! $target->is_active || ! $target->isLawyer())) {
                    throw ValidationException::withMessages(['requested_to' => 'Hedef kullanıcı aktif bir avukat olmalıdır.']);
                }

                $request = new CaseAssignmentRequest(['type' => $type, 'reason' => $data['reason']]);
                $request->case_file_id = $caseFile->id;
                $request->requested_by = $actor->id;
                $request->requested_to = $target?->id;
                $request->save();
                $this->audit->log(AuditAction::CaseAssignmentRequested, $actor, auditable: $request, description: 'Dosya atama/devir talebi oluşturuldu.', newValues: ['type' => $type->value, 'requested_to' => $target?->id, 'reason' => $data['reason']], caseFile: $caseFile);
                DB::afterCommit(fn () => $this->notifications->assignmentRequestCreated($request, $actor));

                return $request;
            });
        } catch (QueryException $exception) {
            if (in_array($exception->errorInfo[0] ?? null, ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['case_file_id' => 'Bu dosya için aynı türde bekleyen bir talebiniz zaten var.']);
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function review(CaseAssignmentRequest $request, array $data, User $actor): CaseAssignmentRequest
    {
        return DB::transaction(function () use ($request, $data, $actor): CaseAssignmentRequest {
            $request = CaseAssignmentRequest::query()->lockForUpdate()->findOrFail($request->id);
            Gate::forUser($actor)->authorize('review', $request);
            if ($request->status !== CaseAssignmentRequestStatus::Pending) {
                throw ValidationException::withMessages(['request' => 'Bu talep daha önce sonuçlandırılmış.']);
            }
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($request->case_file_id);
            if ($caseFile->status->value === 'closed') {
                throw ValidationException::withMessages(['request' => 'Kapalı dosyaya ait talep onaylanamaz.']);
            }
            $decision = CaseAssignmentRequestStatus::from($data['decision']);
            if ($decision === CaseAssignmentRequestStatus::Approved) {
                $this->applyApprovedRequest($request, $caseFile, $data, $actor);
                $action = AuditAction::CaseAssignmentRequestApproved;
            } else {
                $action = AuditAction::CaseAssignmentRequestRejected;
            }

            $oldRequestedTo = $request->requested_to;
            $request->forceFill([
                'status' => $decision,
                'requested_to' => $decision === CaseAssignmentRequestStatus::Approved && $request->type === CaseAssignmentRequestType::Transfer
                    ? ($data['requested_to'] ?? $request->requested_to)
                    : $request->requested_to,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'decision_note' => $data['decision_note'] ?? null,
            ])->save();
            $this->audit->log($action, $actor, auditable: $request, description: 'Dosya atama/devir talebi sonuçlandırıldı.', oldValues: ['status' => CaseAssignmentRequestStatus::Pending->value, 'requested_to' => $oldRequestedTo], newValues: ['status' => $decision->value, 'requested_to' => $request->requested_to, 'reviewed_by' => $actor->id, 'reviewed_at' => $request->reviewed_at, 'decision_note' => $request->decision_note], caseFile: $caseFile);
            DB::afterCommit(fn () => $this->notifications->assignmentRequestDecided($request, $actor));

            return $request;
        });
    }

    public function cancel(CaseAssignmentRequest $request, User $actor): CaseAssignmentRequest
    {
        return DB::transaction(function () use ($request, $actor): CaseAssignmentRequest {
            $request = CaseAssignmentRequest::query()->lockForUpdate()->findOrFail($request->id);
            Gate::forUser($actor)->authorize('cancel', $request);
            $request->forceFill(['status' => CaseAssignmentRequestStatus::Cancelled])->save();
            $this->audit->log(AuditAction::CaseAssignmentRequestCancelled, $actor, auditable: $request, description: 'Dosya atama/devir talebi iptal edildi.', oldValues: ['status' => CaseAssignmentRequestStatus::Pending->value], newValues: ['status' => CaseAssignmentRequestStatus::Cancelled->value], caseFile: $request->caseFile);

            return $request;
        });
    }

    /** @param array<string, mixed> $data */
    private function applyApprovedRequest(CaseAssignmentRequest $request, CaseFile $caseFile, array $data, User $actor): void
    {
        $activeAssignments = $caseFile->assignments()->whereNull('ended_at')->lockForUpdate()->get();
        $lawyerIds = $activeAssignments->pluck('lawyer_id')->map(fn ($id): int => (int) $id);
        $leadLawyerId = (int) $activeAssignments->first(fn ($assignment): bool => $assignment->role === CaseAssignmentRole::Lead)?->lawyer_id;

        if ($request->type === CaseAssignmentRequestType::Transfer) {
            $targetId = (int) ($data['requested_to'] ?? $request->requested_to);
            if ($targetId === 0) {
                throw ValidationException::withMessages(['requested_to' => 'Onay için hedef avukat seçilmelidir.']);
            }
            $target = User::query()->lockForUpdate()->findOrFail($targetId);
            if (! $target->is_active || ! $target->isLawyer()) {
                throw ValidationException::withMessages(['requested_to' => 'Hedef kullanıcı aktif bir avukat olmalıdır.']);
            }
            $requesterAssignment = $activeAssignments->firstWhere('lawyer_id', $request->requested_by);
            if ($requesterAssignment === null) {
                throw ValidationException::withMessages(['request' => 'Talep eden avukat artık bu dosyada aktif değil.']);
            }
            $lawyerIds = $lawyerIds->reject(fn (int $id): bool => $id === $request->requested_by)->push($targetId)->unique()->values();
            if ($requesterAssignment->role === CaseAssignmentRole::Lead || ! empty($data['make_lead'])) {
                $leadLawyerId = $targetId;
            }
        } else {
            $targetId = $request->requested_by;
            if ($activeAssignments->contains('lawyer_id', $targetId)) {
                throw ValidationException::withMessages(['request' => 'Talep eden avukat bu dosyaya zaten atanmış.']);
            }
            $lawyerIds = $lawyerIds->push($targetId)->unique()->values();
            if ($leadLawyerId === 0 || ! empty($data['make_lead'])) {
                $leadLawyerId = $targetId;
            }
        }

        $this->assignments->sync($caseFile, $actor, $lawyerIds->all(), $leadLawyerId, 'Onaylanan '.$request->type->label(), $caseFile->lock_version);
    }
}
