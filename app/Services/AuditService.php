<?php

namespace App\Services;

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AuditService
{
    public function safelyLog(AuditAction $action, ?User $user = null, ?Event $event = null, ?Model $auditable = null, ?string $description = null, array $oldValues = [], array $newValues = []): void
    {
        try {
            AuditLog::query()->create([
                'user_id' => $user?->id,
                'event_id' => $event?->id,
                'action' => $action,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'description' => $description,
                'old_values' => $oldValues ?: null,
                'new_values' => $newValues ?: null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
