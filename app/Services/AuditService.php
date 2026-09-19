<?php

namespace App\Services;

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AuditService
{
    public function log(AuditAction $action, ?User $user = null, ?Event $event = null, ?Model $auditable = null, ?string $description = null, array $oldValues = [], array $newValues = [], ?CaseFile $caseFile = null): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'event_id' => $event?->id,
            'case_file_id' => $caseFile?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function safelyLog(AuditAction $action, ?User $user = null, ?Event $event = null, ?Model $auditable = null, ?string $description = null, array $oldValues = [], array $newValues = [], ?CaseFile $caseFile = null): void
    {
        try {
            $this->log($action, $user, $event, $auditable, $description, $oldValues, $newValues, $caseFile);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
