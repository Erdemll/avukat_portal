<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaseFileAssistantService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<int, int|string> $assistantIds */
    public function sync(CaseFile $caseFile, array $assistantIds, int $expectedLockVersion, User $actor): void
    {
        DB::transaction(function () use ($caseFile, $assistantIds, $expectedLockVersion, $actor): void {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
            Gate::forUser($actor)->authorize('assignAssistants', $caseFile);
            if ($caseFile->lock_version !== $expectedLockVersion) {
                throw ValidationException::withMessages(['lock_version' => 'Dosya başka bir kullanıcı tarafından güncellendi. Sayfayı yenileyip tekrar deneyin.']);
            }

            $assistantIds = collect($assistantIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values()->all();
            $validCount = User::query()->whereKey($assistantIds)->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->where('slug', 'assistant'))->count();
            if ($validCount !== count($assistantIds)) {
                throw ValidationException::withMessages(['assistant_ids' => 'Yalnız aktif asistanlar dosyaya eklenebilir.']);
            }

            $oldIds = $caseFile->assistants()->pluck('users.id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
            if ($oldIds === $assistantIds) {
                return;
            }

            $caseFile->assistants()->sync($assistantIds);
            $caseFile->forceFill(['lock_version' => $caseFile->lock_version + 1])->save();
            $this->audit->log(AuditAction::CaseFileAssistantsChanged, $actor, auditable: $caseFile, description: 'Hukuki dosya asistanları güncellendi.', oldValues: ['assistant_ids' => $oldIds], newValues: ['assistant_ids' => $assistantIds], caseFile: $caseFile);
        });
    }
}
