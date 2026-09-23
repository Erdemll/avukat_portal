<?php

namespace App\Services;

use App\AuditAction;
use App\CaseAssignmentRequestStatus;
use App\DeadlineStatus;
use App\HearingStatus;
use App\LegalTaskStatus;
use App\Models\CaseAssignmentRequest;
use App\Models\CaseFile;
use App\Models\CaseFileAssignment;
use App\Models\Client;
use App\Models\Deadline;
use App\Models\Event;
use App\Models\Hearing;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LawyerRetirementService
{
    public function __construct(
        private CaseFileAssignmentService $assignments,
        private EventManagementService $events,
        private CaseWorkflowNotificationService $notifications,
        private AuditService $audit,
    ) {}

    public function retire(User $lawyer, User $replacement, User $manager): void
    {
        DB::transaction(function () use ($lawyer, $replacement, $manager): void {
            Gate::forUser($manager)->authorize('update', $lawyer);
            $lawyer = User::query()->lockForUpdate()->findOrFail($lawyer->id);
            $replacement = User::query()->lockForUpdate()->findOrFail($replacement->id);

            if (! $lawyer->isLawyer() || ! $lawyer->is_active || $replacement->is($lawyer) || ! $replacement->isLawyer() || ! $replacement->is_active) {
                throw ValidationException::withMessages(['replacement_lawyer_id' => 'Devir için farklı ve aktif bir avukat seçilmelidir.']);
            }

            $caseFileIds = CaseFileAssignment::query()
                ->where('lawyer_id', $lawyer->id)
                ->whereNull('ended_at')
                ->orderBy('case_file_id')
                ->pluck('case_file_id');

            foreach ($caseFileIds as $caseFileId) {
                $caseFile = CaseFile::query()->findOrFail($caseFileId);
                $lawyerIds = $caseFile->activeLawyers()->pluck('users.id')
                    ->reject(fn (int $id): bool => $id === $lawyer->id)
                    ->push($replacement->id)
                    ->unique()
                    ->values()
                    ->all();

                $this->assignments->sync($caseFile, $manager, $lawyerIds, $replacement->id, 'Avukat hesabı pasifleştirilirken dosya devri.', $caseFile->lock_version);
            }

            Event::query()->withTrashed()->where('assigned_lawyer_id', $lawyer->id)->orderBy('id')->get()
                ->each(fn (Event $event) => $this->events->reassign($event, $replacement, $manager));

            Hearing::query()->where('lawyer_id', $lawyer->id)
                ->whereIn('status', [HearingStatus::Scheduled->value, HearingStatus::Postponed->value])
                ->orderBy('id')->lockForUpdate()->get()
                ->each(function (Hearing $hearing) use ($lawyer, $replacement, $manager): void {
                    $hearing->forceFill(['lawyer_id' => $replacement->id, 'reminder_sent_at' => null, 'lock_version' => $hearing->lock_version + 1])->save();
                    $this->audit->log(AuditAction::HearingUpdated, $manager, auditable: $hearing, description: 'Avukat pasifleştirme kapsamında duruşma devredildi.', oldValues: ['lawyer_id' => $lawyer->id], newValues: ['lawyer_id' => $replacement->id], caseFile: $hearing->caseFile);
                });

            Deadline::query()->where('assigned_lawyer_id', $lawyer->id)
                ->where('status', DeadlineStatus::Open->value)
                ->orderBy('id')->lockForUpdate()->get()
                ->each(function (Deadline $deadline) use ($lawyer, $replacement, $manager): void {
                    $deadline->forceFill(['assigned_lawyer_id' => $replacement->id, 'reminder_sent_at' => null, 'lock_version' => $deadline->lock_version + 1])->save();
                    $this->audit->log(AuditAction::DeadlineUpdated, $manager, auditable: $deadline, description: 'Avukat pasifleştirme kapsamında süre devredildi.', oldValues: ['assigned_lawyer_id' => $lawyer->id], newValues: ['assigned_lawyer_id' => $replacement->id], caseFile: $deadline->caseFile);
                });

            LegalTask::query()->where('assigned_to', $lawyer->id)
                ->whereIn('status', [LegalTaskStatus::Pending->value, LegalTaskStatus::InProgress->value])
                ->orderBy('id')->lockForUpdate()->get()
                ->each(function (LegalTask $task) use ($lawyer, $replacement, $manager): void {
                    $task->forceFill(['assigned_to' => $replacement->id, 'lock_version' => $task->lock_version + 1])->save();
                    $this->audit->log(AuditAction::LegalTaskUpdated, $manager, auditable: $task, description: 'Avukat pasifleştirme kapsamında görev devredildi.', oldValues: ['assigned_to' => $lawyer->id], newValues: ['assigned_to' => $replacement->id], caseFile: $task->caseFile);
                });

            Client::query()->where(fn ($query) => $query
                ->where('responsible_lawyer_id', $lawyer->id)
                ->orWhere(fn ($unassigned) => $unassigned->where('created_by', $lawyer->id)->whereNull('responsible_lawyer_id')))
                ->orderBy('id')->lockForUpdate()->get()
                ->each(function (Client $client) use ($replacement, $manager): void {
                    $previousResponsible = $client->responsible_lawyer_id;
                    $client->forceFill(['responsible_lawyer_id' => $replacement->id])->save();
                    $this->audit->log(AuditAction::ClientUpdated, $manager, auditable: $client, description: 'Avukat pasifleştirme kapsamında müvekkil sorumluluğu devredildi.', oldValues: ['responsible_lawyer_id' => $previousResponsible], newValues: ['responsible_lawyer_id' => $replacement->id]);
                });

            CaseAssignmentRequest::query()->where('status', CaseAssignmentRequestStatus::Pending->value)
                ->where(fn ($query) => $query->where('requested_by', $lawyer->id)->orWhere('requested_to', $lawyer->id))
                ->orderBy('id')->lockForUpdate()->get()
                ->each(function (CaseAssignmentRequest $request) use ($manager): void {
                    $request->forceFill([
                        'status' => CaseAssignmentRequestStatus::Cancelled,
                        'reviewed_by' => $manager->id,
                        'reviewed_at' => now(),
                        'decision_note' => 'Avukat hesabı pasifleştirildiği için iptal edildi.',
                    ])->save();
                    $this->audit->log(AuditAction::CaseAssignmentRequestCancelled, $manager, auditable: $request, description: 'Avukat pasifleştirme kapsamında bekleyen talep iptal edildi.', oldValues: ['status' => CaseAssignmentRequestStatus::Pending->value], newValues: ['status' => CaseAssignmentRequestStatus::Cancelled->value], caseFile: $request->caseFile);
                    DB::afterCommit(fn () => $this->notifications->assignmentRequestDecided($request, $manager));
                });

            if ($lawyer->email !== null) {
                DB::table('password_reset_tokens')->where('email', $lawyer->email)->delete();
            }
            $lawyer->notifications()->delete();
            $lawyer->twoFactorChallenges()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $lawyer->id)->delete();
            }

            $lawyer->forceFill([
                'is_active' => false,
                'email' => null,
                'email_verified_at' => null,
                'tc_kimlik_no' => null,
                'phone' => null,
                'password' => Str::password(32),
                'remember_token' => Str::random(60),
                'two_factor_enabled_at' => null,
                'last_login_at' => null,
            ])->save();
            $this->audit->log(AuditAction::UserDeactivated, $manager, auditable: $lawyer, description: 'Avukatın işleri devredildi ve hesabı pasifleştirildi.', newValues: ['user_id' => $lawyer->id, 'replacement_lawyer_id' => $replacement->id]);
        }, attempts: 3);
    }
}
