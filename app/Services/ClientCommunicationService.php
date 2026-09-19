<?php

namespace App\Services;

use App\AuditAction;
use App\Models\CaseFile;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ClientCommunicationService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): ClientCommunication
    {
        return DB::transaction(function () use ($data, $actor): ClientCommunication {
            $caseFile = ! empty($data['case_file_id']) ? CaseFile::query()->lockForUpdate()->findOrFail($data['case_file_id']) : null;
            $client = Client::query()->lockForUpdate()->findOrFail($data['client_id']);
            Gate::forUser($actor)->authorize('view', $client);
            if ($caseFile !== null) {
                Gate::forUser($actor)->authorize('view', $caseFile);
                $this->ensureClientBelongsToCase($client, $caseFile);
            }
            $communication = new ClientCommunication(Arr::except($data, ['client_id', 'case_file_id']));
            $communication->client_id = $client->id;
            $communication->case_file_id = $caseFile?->id;
            $communication->user_id = $actor->id;
            $communication->save();
            $this->audit->log(AuditAction::ClientCommunicationCreated, $actor, auditable: $communication, description: 'Müvekkil iletişimi kaydedildi.', newValues: $communication->only(['client_id', 'case_file_id', 'type', 'subject', 'description', 'communication_at']), caseFile: $caseFile);

            return $communication;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(ClientCommunication $communication, array $data, User $actor): ClientCommunication
    {
        return DB::transaction(function () use ($communication, $data, $actor): ClientCommunication {
            $communication = ClientCommunication::query()->lockForUpdate()->findOrFail($communication->id);
            $caseFileIds = collect([$communication->case_file_id, $data['case_file_id'] ?? null])->filter()->unique()->sort()->values();
            $caseFiles = CaseFile::query()->whereIn('id', $caseFileIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $sourceCaseFile = $communication->case_file_id !== null ? $caseFiles->get($communication->case_file_id) : null;
            if ($sourceCaseFile !== null) {
                Gate::forUser($actor)->authorize('view', $sourceCaseFile);
            }
            Gate::forUser($actor)->authorize('update', $communication);
            if ($communication->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'İletişim kaydı başka bir kullanıcı tarafından güncellendi.']);
            }
            $clientIds = collect([$communication->client_id, $data['client_id']])->unique()->sort()->values();
            $clients = Client::query()->whereIn('id', $clientIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $client = $clients->get((int) $data['client_id']);
            $caseFile = ! empty($data['case_file_id']) ? $caseFiles->get((int) $data['case_file_id']) : null;
            Gate::forUser($actor)->authorize('view', $client);
            if ($caseFile !== null) {
                Gate::forUser($actor)->authorize('view', $caseFile);
                $this->ensureClientBelongsToCase($client, $caseFile);
            }
            $oldValues = $communication->only(['client_id', 'case_file_id', 'type', 'subject', 'description', 'communication_at']);
            $communication->fill(Arr::except($data, ['client_id', 'case_file_id', 'lock_version']));
            $communication->client_id = $client->id;
            $communication->case_file_id = $caseFile?->id;
            $communication->lock_version++;
            $communication->save();
            $this->audit->log(AuditAction::ClientCommunicationUpdated, $actor, auditable: $communication, description: 'Müvekkil iletişimi güncellendi.', oldValues: $oldValues, newValues: $communication->only(['client_id', 'case_file_id', 'type', 'subject', 'description', 'communication_at']), caseFile: $caseFile);

            return $communication;
        });
    }

    private function ensureClientBelongsToCase(Client $client, CaseFile $caseFile): void
    {
        if (! $caseFile->activeParties()->whereKey($client->party_id)->exists()) {
            throw ValidationException::withMessages(['case_file_id' => 'Seçilen dosya bu müvekkile ait değil.']);
        }
    }
}
