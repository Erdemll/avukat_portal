<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DocumentFolderService
{
    private const DEFAULT_FOLDERS = [
        'Dilekçeler',
        'Tebligatlar',
        'Duruşma Tutanakları',
        'Bilirkişi Raporları',
        'Deliller',
        'Sözleşmeler',
        'Faturalar',
        'İcra Evrakları',
        'Diğer',
    ];

    public function __construct(private AuditService $audit) {}

    /** @return Collection<int, DocumentFolder> */
    public function ensureDefaults(CaseFile $caseFile, User $actor): Collection
    {
        return DB::transaction(function () use ($caseFile, $actor): Collection {
            $folders = new Collection;
            foreach (self::DEFAULT_FOLDERS as $name) {
                $folder = DocumentFolder::query()->firstOrCreate(
                    ['case_file_id' => $caseFile->id, 'name' => $name],
                    ['created_by' => $actor->id],
                );
                $folders->push($folder);
            }

            return $folders;
        });
    }

    public function create(CaseFile $caseFile, string $name, User $actor): DocumentFolder
    {
        return DB::transaction(function () use ($caseFile, $name, $actor): DocumentFolder {
            $caseFile = CaseFile::query()->lockForUpdate()->findOrFail($caseFile->id);
            Gate::forUser($actor)->authorize('manageDocuments', $caseFile);
            if ($caseFile->status->value === 'closed') {
                throw ValidationException::withMessages(['name' => 'Kapalı dosyada klasör oluşturulamaz.']);
            }
            $folder = new DocumentFolder(['name' => $name]);
            $folder->case_file_id = $caseFile->id;
            $folder->created_by = $actor->id;
            $folder->save();
            $this->audit->log(AuditAction::DocumentFolderCreated, $actor, auditable: $folder, description: 'Evrak klasörü oluşturuldu.', newValues: ['name' => $name], caseFile: $caseFile);

            return $folder;
        });
    }
}
