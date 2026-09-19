<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\CaseFileParty;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CaseFilePartyService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function add(CaseFile $caseFile, array $data, User $actor): CaseFileParty
    {
        return DB::transaction(function () use ($caseFile, $data, $actor): CaseFileParty {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
            Gate::forUser($actor)->authorize('manageParties', $caseFile);
            $party = isset($data['party_id'])
                ? Party::query()->findOrFail($data['party_id'])
                : $this->createParty($data, $actor);

            $caseParty = CaseFileParty::query()
                ->where('case_file_id', $caseFile->id)
                ->where('party_id', $party->id)
                ->where('role', $data['role'])
                ->whereNull('left_at')
                ->first() ?? new CaseFileParty;
            $caseParty->fill([
                'role' => $data['role'],
                'side' => $data['side'],
                'is_primary' => (bool) ($data['is_primary'] ?? false),
                'joined_at' => $caseParty->exists ? $caseParty->joined_at : now(),
                'left_at' => null,
            ]);
            $caseParty->case_file_id = $caseFile->id;
            $caseParty->party_id = $party->id;
            $caseParty->added_by ??= $actor->id;
            $caseParty->save();

            $caseFile->increment('lock_version');
            $this->audit->log(AuditAction::CaseFilePartyAdded, $actor, auditable: $caseParty, description: 'Hukuki dosyaya taraf eklendi.', newValues: ['party_id' => $party->id, 'role' => $data['role'], 'side' => $data['side']], caseFile: $caseFile);

            return $caseParty->load('party');
        });
    }

    public function remove(CaseFile $caseFile, CaseFileParty $caseParty, User $actor): void
    {
        DB::transaction(function () use ($caseFile, $caseParty, $actor): void {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
            Gate::forUser($actor)->authorize('manageParties', $caseFile);
            $caseParty = CaseFileParty::query()->where('case_file_id', $caseFile->id)->whereNull('left_at')->findOrFail($caseParty->id);
            $caseParty->forceFill(['left_at' => now()])->save();
            $caseFile->increment('lock_version');
            $this->audit->log(AuditAction::CaseFilePartyRemoved, $actor, auditable: $caseParty, description: 'Hukuki dosyadaki taraf ilişkisi sonlandırıldı.', oldValues: ['party_id' => $caseParty->party_id, 'role' => $caseParty->role->value], caseFile: $caseFile);
        });
    }

    /** @param array<string, mixed> $data */
    private function createParty(array $data, User $actor): Party
    {
        $party = new Party(Arr::only($data, ['type', 'name', 'surname', 'company_name', 'phone', 'email', 'address']));
        $party->created_by = $actor->id;
        $party->save();

        return $party;
    }
}
